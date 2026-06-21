<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\TimelineLomba;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function normalizedEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function isCurrentUserMemberOfKarya(KaryaPeserta $karya, ?string $email): bool
    {
        $email = $this->normalizedEmail($email);
        if ($email === '') {
            return false;
        }

        return collect($karya->anggota_tim ?? [])->contains(function ($anggota) use ($email) {
            if (!is_array($anggota)) {
                return false;
            }

            return $this->normalizedEmail($anggota['email'] ?? null) === $email;
        });
    }

    private function resolveEdisiAktifOrFail(): Edition
    {
        $tahunSekarang = (int) now()->format('Y');
        $edisi = Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->where('status', '!=', 'arsip')->where('tahun', $tahunSekarang)->first()
            ?? Edition::query()->where('status', '!=', 'arsip')->orderByDesc('tahun')->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        abort_if(!$edisi, 500, 'Edisi aktif belum tersedia.');
        session(['edisi_aktif_id' => $edisi->id]);

        return $edisi;
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        $userEmail = $this->normalizedEmail($request->user()->email);
        $karyaSemua = KaryaPeserta::query()
            ->with('edisi:id,nama,tahun,status,aktif')
            ->where(function ($query) use ($request, $userEmail) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$userEmail],
                    );
            })
            ->orderByDesc('updated_at')
            ->get();

        $submissionCount = $karyaSemua->where('status', 'submitted')->count();
        $nominasi = $karyaSemua->where('lolos_nominasi', true)->values();
        $edisiOpsi = $karyaSemua
            ->pluck('edisi')
            ->filter()
            ->unique('id')
            ->sortByDesc('tahun')
            ->map(function ($edisiItem) {
                return [
                    'id' => $edisiItem->id,
                    'tahun' => $edisiItem->tahun,
                    'label' => $edisiItem->nama . ' (' . $edisiItem->tahun . ')',
                ];
            })
            ->values();

        $karyaList = $karyaSemua->map(function (KaryaPeserta $item) use ($request) {
            return [
                'id' => $item->id,
                'nama_karya' => $item->nama_karya,
                'nama_kategori' => $item->nama_kategori,
                'tahun' => $item->edisi?->tahun,
                'edisi_label' => $item->edisi?->nama . ' (' . $item->edisi?->tahun . ')',
                'status' => $item->status,
                'peran' => $this->isCurrentUserMemberOfKarya($item, $request->user()->email)
                    && (int) $item->user_id !== (int) $request->user()->id
                    ? 'Anggota'
                    : 'Ketua',
                'lolos_nominasi' => (bool) $item->lolos_nominasi,
            ];
        })->values();

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
                    'mulai_pada' => $item->mulai_pada?->format('d M Y'),
                    'selesai_pada' => $item->selesai_pada?->format('d M Y'),
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

        return Inertia::render('Peserta/Dashboard', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'edisiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'statusTim' => $karyaSemua->isEmpty()
                ? 'Belum Terdaftar'
                : ($submissionCount > 0 ? 'Submission Lengkap' : 'Draft Tersimpan'),
            'totalKarya' => $karyaSemua->count(),
            'submissionCount' => $submissionCount,
            'nominasiCount' => $nominasi->count(),
            'edisiOpsi' => $edisiOpsi,
            'karyaList' => $karyaList,
            'timeline' => $timeline,
            'weather' => $weather,
            'nominasiList' => $nominasi->map(function (KaryaPeserta $item) {
                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'peran' => $this->isCurrentUserMemberOfKarya($item, request()->user()->email)
                        && (int) $item->user_id !== (int) request()->user()->id
                        ? 'Anggota'
                        : 'Ketua',
                    'pameran_logo_url' => $item->pameran_logo_path
                        ? route('peserta.pameran.logo.preview', ['karya' => $item->id])
                        : null,
                    'pameran_submitted_at' => $item->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })->values(),
        ]);
    }
}
