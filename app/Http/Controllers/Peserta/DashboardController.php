<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\TimelineLomba;
use App\Services\NominationAnnouncementService;
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

    private function nominationVisibleForEdition(Edition $edisi): bool
    {
        return app(NominationAnnouncementService::class)->isAnnouncementWindowOpenForEdition((int) $edisi->id);
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        $userEmail = $this->normalizedEmail($request->user()->email);
        $nominasiTerbuka = $edisi->status === 'aktif'
            && $this->nominationVisibleForEdition($edisi);
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
        $karyaEdisiAktif = $karyaSemua->where('edisi_lomba_id', $edisi->id)->values();
        $nominasi = $nominasiTerbuka
            ? $karyaEdisiAktif->where('lolos_nominasi', true)->values()
            : collect();
        $nominasiNotification = [];
        if ($nominasiTerbuka && $nominasi->isNotEmpty()) {
            $signature = $nominasi->pluck('id')->implode('-');
            $seenKey = "nominasi_notification_seen_{$edisi->id}";
            if (session($seenKey) !== $signature) {
                session([$seenKey => $signature]);
                $nominasiNotification = $nominasi
                    ->map(fn (KaryaPeserta $item) => $item->nama_karya)
                    ->filter()
                    ->values()
                    ->all();
            }
        }
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

        return Inertia::render('Peserta/Dashboard', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'edisiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'nominasiTerbuka' => $nominasiTerbuka,
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
            'flash' => array_filter([
                'welcome' => $request->session()->pull('welcome'),
                'success' => $request->session()->pull('success'),
                'error' => $request->session()->pull('error'),
                'nominasi' => !empty($nominasiNotification)
                    ? $nominasiNotification
                    : null,
            ]),
        ]);
    }
}
