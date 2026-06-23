<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\PenilaianTahapDua;
use App\Models\PenugasanJuriKategori;
use App\Models\TimelineLomba;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class JuriDashboardController extends Controller
{
    private function resolveEdisiKonteks(Request $request): Edition
    {
        $user = $request->user();

        $query = Edition::query()
            ->whereHas('roles', function ($q) use ($user) {
                $q->where('roles.name', 'juri')
                    ->where('edisi_lomba_user_role.user_id', $user->id);
            })
            ->orderByDesc('tahun');

        $daftar = $query->get();
        abort_if($daftar->isEmpty(), 404, 'Edisi tidak ditemukan.');

        $selectedId = (int) session('edisi_aktif_id');
        $edisi = $daftar->firstWhere('id', $selectedId)
            ?? $daftar->firstWhere('status', 'aktif')
            ?? $daftar->firstWhere('aktif', true)
            ?? $daftar->first();

        session(['edisi_aktif_id' => $edisi->id]);
        return $edisi;
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $juriId = (int) $request->user()->id;

        $tahap1Assignments = PenugasanJuriKategori::query()
            ->with('kategori:id,nama')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('juri_id', $juriId)
            ->whereIn('tahap', ['tahap_1', 'tahap_1_2'])
            ->get();

        $kategoriTahap1Ids = $tahap1Assignments
            ->pluck('kategori_lomba_id')
            ->unique()
            ->values()
            ->all();

        $karyaTahap1 = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->when(
                empty($kategoriTahap1Ids),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('kategori_lomba_id', $kategoriTahap1Ids)
            )
            ->get(['id', 'nilai_tahap_1']);

        $totalTahap1 = $karyaTahap1->count();
        $dinilaiTahap1 = $karyaTahap1->whereNotNull('nilai_tahap_1')->count();
        $belumTahap1 = max(0, $totalTahap1 - $dinilaiTahap1);

        $tahap2Assignments = PenugasanJuriKategori::query()
            ->with('kategori:id,nama')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('juri_id', $juriId)
            ->whereIn('tahap', ['tahap_2', 'tahap_1_2'])
            ->get();

        $kategoriTahap2Ids = $tahap2Assignments
            ->pluck('kategori_lomba_id')
            ->unique()
            ->values()
            ->all();

        $karyaTahap2Ids = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->when(
                empty($kategoriTahap2Ids),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('kategori_lomba_id', $kategoriTahap2Ids)
            )
            ->pluck('id')
            ->all();

        $totalTahap2 = count($karyaTahap2Ids);
        $dinilaiTahap2 = 0;
        if ($totalTahap2 > 0) {
            $dinilaiTahap2 = PenilaianTahapDua::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->whereIn('karya_peserta_id', $karyaTahap2Ids)
                ->distinct('karya_peserta_id')
                ->count('karya_peserta_id');
        }

        $belumTahap2 = max(0, $totalTahap2 - $dinilaiTahap2);

        $timeline = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->orderByRaw("CASE fase_kunci
                WHEN 'opening' THEN 1
                WHEN 'pendaftaran' THEN 2
                WHEN 'penjurian_tahap_1' THEN 3
                WHEN 'pengumuman_nominasi' THEN 4
                WHEN 'pameran_karya' THEN 5
                WHEN 'penjurian_tahap_2' THEN 6
                WHEN 'awarding' THEN 7
                ELSE 99
            END")
            ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mulai_pada')
            ->orderBy('id')
            ->get(['id', 'judul', 'mulai_pada', 'selesai_pada', 'is_tba', 'deskripsi', 'aktif'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'mulai_pada' => $item->mulai_pada?->toIso8601String(),
                    'selesai_pada' => $item->selesai_pada?->toIso8601String(),
                    'is_tba' => (bool) $item->is_tba,
                    'deskripsi' => $item->deskripsi,
                    'aktif' => (bool) $item->aktif,
                ];
            })->values();

        $weather = null;
        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'current' => 'weathercode,is_day',
                'timezone' => 'Asia/Jakarta',
            ]);
            if ($response->ok()) {
                $current = $response->json('current');
                if (is_array($current)) {
                    $weather = [
                        'code' => $current['weathercode'] ?? null,
                        'is_day' => $current['is_day'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $weather = null;
        }

        return Inertia::render('Juri/Dashboard', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'weather' => $weather,
            'tahap1' => [
                'total' => $totalTahap1,
                'dinilai' => $dinilaiTahap1,
                'belum' => $belumTahap1,
                'kategori' => $tahap1Assignments
                    ->map(fn ($row) => $row->kategori?->nama)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ],
            'tahap2' => [
                'total' => $totalTahap2,
                'dinilai' => $dinilaiTahap2,
                'belum' => $belumTahap2,
                'kategori' => $tahap2Assignments
                    ->map(fn ($row) => $row->kategori?->nama)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ],
            'timeline' => $timeline,
        ]);
    }
}
