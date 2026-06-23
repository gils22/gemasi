<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\KategoriLomba;
use App\Models\PenilaianTahapDua;
use App\Models\Role;
use App\Models\TimelineLomba;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private function resolveEdisiKonteks(): Edition
    {
        $selectedId = (int) session('edisi_aktif_id');
        if ($selectedId > 0) {
            $bySession = Edition::query()->find($selectedId);
            if ($bySession) {
                return $bySession;
            }
        }

        $tahunSekarang = (int) now()->format('Y');

        $edisi = Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->where('tahun', $tahunSekarang)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        abort_if(!$edisi, 500, 'Edisi lomba belum tersedia.');

        session(['edisi_aktif_id' => $edisi->id]);

        return $edisi;
    }

    public function index()
    {
        $edisi = $this->resolveEdisiKonteks();

        $roleAdminId = Role::query()->where('name', 'admin')->value('id');
        $roleJuriId = Role::query()->where('name', 'juri')->value('id');
        $rolePesertaId = Role::query()->where('name', 'peserta')->value('id');

        $totalUser = DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->distinct()
            ->count('user_id');

        $totalAdmin = DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('role_id', $roleAdminId)
            ->distinct()
            ->count('user_id');

        $totalJuri = DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('role_id', $roleJuriId)
            ->distinct()
            ->count('user_id');

        $totalAdminJuri = DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->whereIn('role_id', array_filter([$roleAdminId, $roleJuriId]))
            ->distinct()
            ->count('user_id');

        $totalPeserta = User::query()
            ->whereHas('editionRoles', function ($q) use ($edisi, $rolePesertaId) {
                $q->where('edisi_lomba_user_role.edisi_lomba_id', $edisi->id)
                    ->where('edisi_lomba_user_role.role_id', $rolePesertaId);
            })
            ->whereDoesntHave('editionRoles', function ($q) use ($edisi, $roleAdminId, $roleJuriId) {
                $q->where('edisi_lomba_user_role.edisi_lomba_id', $edisi->id)
                    ->whereIn('edisi_lomba_user_role.role_id', array_filter([$roleAdminId, $roleJuriId]));
            })
            ->count();

        $totalKarya = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->count();

        $karyaDraft = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'draft')
            ->count();

        $karyaSubmitted = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->count();

        $karyaNominasi = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->count();

        $karyaDinilaiTahap2 = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->distinct()
            ->count('karya_peserta_id');

        $kategoriCounts = KaryaPeserta::query()
            ->select('kategori_lomba_id', DB::raw('count(*) as total'))
            ->where('edisi_lomba_id', $edisi->id)
            ->groupBy('kategori_lomba_id')
            ->pluck('total', 'kategori_lomba_id');

        $kategoriStats = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(function ($kategori) use ($kategoriCounts) {
                return [
                    'id' => $kategori->id,
                    'nama' => $kategori->nama,
                    'total' => (int) ($kategoriCounts[$kategori->id] ?? 0),
                ];
            })
            ->values();

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
            ->get([
                'id',
                'judul',
                'fase_kunci',
                'mulai_pada',
                'selesai_pada',
                'is_tba',
                'deskripsi',
                'aktif',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'fase_kunci' => $item->fase_kunci,
                    'mulai_pada' => $item->mulai_pada?->toIso8601String(),
                    'selesai_pada' => $item->selesai_pada?->toIso8601String(),
                    'is_tba' => (bool) $item->is_tba,
                    'deskripsi' => $item->deskripsi,
                    'aktif' => (bool) $item->aktif,
                ];
            })
            ->values();

        $weather = null;
        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'current' => 'weathercode,is_day',
                'timezone' => 'Asia/Bangkok',
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

        return Inertia::render('Admin/Dashboard', [
            'statistik' => [
                'total_user' => $totalUser,
                'total_peserta' => $totalPeserta,
                'total_admin' => $totalAdmin,
                'total_juri' => $totalJuri,
                'total_admin_juri' => $totalAdminJuri,
                'total_karya' => $totalKarya,
                'karya_draft' => $karyaDraft,
                'karya_submitted' => $karyaSubmitted,
                'karya_nominasi' => $karyaNominasi,
                'karya_dinilai_tahap_2' => $karyaDinilaiTahap2,
            ],
            'ringkasanEdisi' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'weather' => $weather,
            'kategoriStats' => $kategoriStats,
            'timeline' => $timeline,
        ]);
    }
}
