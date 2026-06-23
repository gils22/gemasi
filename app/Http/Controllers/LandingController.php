<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\LandingSetting;
use App\Models\PanduanLomba;
use App\Models\PemenangFavorit;
use App\Models\PemenangKarya;
use App\Models\KategoriLomba;
use App\Models\BobotPenilaianKategori;
use App\Models\TimelineLomba;
use App\Models\KaryaPeserta;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingController extends Controller
{
    private function decodeKriteria(?string $rawCatatan): array
    {
        $decoded = $rawCatatan ? json_decode($rawCatatan, true) : null;
        if (is_array($decoded) && isset($decoded['kriteria']) && is_array($decoded['kriteria'])) {
            return array_values(array_filter(array_map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }
                $nama = trim((string) ($item['nama'] ?? ''));
                $poin = (float) ($item['poin'] ?? 0);
                $deskripsi = trim((string) ($item['deskripsi'] ?? ''));
                if ($nama === '') {
                    return null;
                }
                return [
                    'label' => $nama,
                    'point' => $poin,
                    'description' => $deskripsi,
                ];
            }, $decoded['kriteria'])));
        }

        return [];
    }

    private function decodeKetentuan(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(
                fn ($item) => is_string($item) ? trim($item) : '',
                $decoded
            )));
        }

        return array_values(array_filter(array_map(
            fn ($line) => trim($line),
            preg_split('/\r\n|\r|\n/', $raw) ?: []
        )));
    }

    private function mapWinnerRow(PemenangKarya $row): array
    {
        $anggota = collect($row->karya?->anggota_tim ?? [])
            ->map(function ($item) {
                $nama = is_array($item) ? ($item['nama'] ?? '-') : '-';
                $nim = is_array($item) ? ($item['nim'] ?? '-') : '-';
                return ['nama' => $nama, 'nim' => $nim];
            })
            ->values()
            ->all();

        $logoUrl = null;
        if ($row->karya?->pameran_logo_path) {
            $logoUrl = route('landing.juara.logo.preview', ['karya' => $row->karya->id]);
        }

        return [
            'edisi_id' => $row->edisi_lomba_id,
            'id' => $row->id,
            'tahun' => $row->edisi?->tahun,
            'nama_edisi' => $row->edisi?->nama,
            'peringkat' => $row->peringkat,
            'kategori' => $row->karya?->nama_kategori ?? $row->kategori?->nama,
            'nama_karya' => $row->karya?->nama_karya,
            'anggota_tim' => $anggota,
            'deskripsi' => $row->karya?->pameran_ringkasan,
            'logo_url' => $logoUrl,
            'logo_name' => $row->karya?->pameran_logo_nama_asli,
            'video_url' => $row->karya?->pameran_link_video,
            'pameran_submitted_at' => $row->karya?->pameran_submitted_at?->format('d M Y, H:i'),
        ];
    }

    private function winnersPayload(): array
    {
        $edisi = Edition::query()
            ->orderByDesc('id')
            ->get(['id', 'nama', 'tahun', 'status']);

        $pemenang = PemenangKarya::query()
            ->with([
                'karya:id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_path,pameran_logo_nama_asli,pameran_logo_mime_type,pameran_submitted_at',
                'kategori:id,nama',
                'edisi:id,nama,tahun',
            ])
            ->orderByDesc('edisi_lomba_id')
            ->orderBy('kategori_lomba_id')
            ->orderBy('peringkat')
            ->get()
            ->map(fn ($row) => $this->mapWinnerRow($row))
            ->values();

        $gabungan = $pemenang
            ->groupBy('edisi_id')
            ->map(function ($items) {
                return $items->sortBy([
                    ['kategori', 'asc'],
                    ['peringkat', 'asc'],
                ])->values();
            })
            ->all();

        $favorit = PemenangFavorit::query()
            ->with([
                'karya:id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_path,pameran_logo_nama_asli,pameran_submitted_at',
                'edisi:id,nama,tahun',
            ])
            ->orderByDesc('edisi_lomba_id')
            ->orderBy('peringkat')
            ->get()
            ->map(function ($row) {
                $logoUrl = $row->karya?->pameran_logo_path
                    ? route('landing.juara.logo.preview', ['karya' => $row->karya->id])
                    : null;

                return [
                    'edisi_id' => $row->edisi_lomba_id,
                    'id' => $row->id,
                    'tahun' => $row->edisi?->tahun,
                    'nama_edisi' => $row->edisi?->nama,
                    'peringkat' => $row->peringkat,
                    'kategori' => $row->karya?->nama_kategori,
                    'nama_karya' => $row->karya?->nama_karya,
                    'anggota_tim' => collect($row->karya?->anggota_tim ?? [])
                        ->map(function ($item) {
                            return [
                                'nama' => is_array($item) ? ($item['nama'] ?? '-') : '-',
                                'nim' => is_array($item) ? ($item['nim'] ?? '-') : '-',
                            ];
                        })
                        ->values()
                        ->all(),
                    'deskripsi' => $row->karya?->pameran_ringkasan,
                    'logo_url' => $logoUrl,
                    'logo_name' => $row->karya?->pameran_logo_nama_asli,
                    'video_url' => $row->karya?->pameran_link_video,
                    'pameran_submitted_at' => $row->karya?->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })
            ->groupBy('edisi_id')
            ->map(function ($items) {
                return $items->sortBy('peringkat')->values();
            })
            ->all();

        return [
            'daftarEdisi' => $edisi,
            'pemenangPerEdisi' => $gabungan,
            'favoritPerEdisi' => $favorit,
        ];
    }

    public function index()
    {
        $setting = LandingSetting::query()->first();
        $edisi = null;
        if ($setting?->landing_edisi_lomba_id) {
            $edisi = Edition::query()->find($setting->landing_edisi_lomba_id);
        }
        $edisi = $edisi
            ?? Edition::query()
                ->orderByDesc('tahun')
                ->get(['id', 'nama', 'tahun', 'status', 'aktif'])
                ->firstWhere('id', (int) session('edisi_aktif_id'))
            ?? Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        $kategori = collect();
        $timeline = collect();
        if ($edisi) {
            $kategoriRaw = KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'slug', 'deskripsi', 'icon_path']);

            $bobotMap = BobotPenilaianKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->get()
                ->keyBy('kategori_lomba_id');

            $kategori = $kategoriRaw->map(function (KategoriLomba $item) use ($bobotMap) {
                $bobot = $bobotMap->get($item->id);
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'slug' => $item->slug,
                    'deskripsi' => $item->deskripsi,
                    'icon_url' => $item->icon_path ? route('kategori.icon.preview', ['kategori' => $item->id]) : null,
                    'weights' => $this->decodeKriteria($bobot?->catatan),
                ];
            })->values();

            $timeline = TimelineLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('aktif', true)
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
                    'mulai_pada',
                    'selesai_pada',
                    'is_tba',
                    'deskripsi',
                ]);
        }

        return Inertia::render('Landing/Index', [
            'landing' => $setting ? [
                'landing_edisi_lomba_id' => $setting->landing_edisi_lomba_id,
                'hero_badge' => $setting->hero_badge,
                'hero_title' => $setting->hero_title,
                'hero_subtitle' => $setting->hero_subtitle,
                'about_text' => $setting->about_text,
                'video_file_url' => $setting->video_path ? Storage::disk('public')->url($setting->video_path) : null,
                'video_file_name' => $setting->video_path ? basename($setting->video_path) : null,
                'video_stream_url' => $setting->video_path ? route('landing.motion.video.preview') : null,
                'login_carousel_items' => collect($setting->login_carousel_items ?? [])
                    ->map(function ($item) {
                        if (!is_array($item)) {
                            return null;
                        }

                        $path = trim((string) ($item['path'] ?? ''));
                        if ($path === '') {
                            return null;
                        }

                        return [
                            'name' => trim((string) ($item['name'] ?? basename($path))) ?: basename($path),
                            'preview_url' => route('landing.carousel.preview', ['path' => $path]),
                        ];
                    })
                    ->filter()
                    ->values(),
                'gallery_items' => collect($setting->gallery_items ?? [])
                    ->map(function ($item) {
                        if (!is_array($item)) {
                            return null;
                        }

                        $path = trim((string) ($item['path'] ?? ''));
                        if ($path === '') {
                            return null;
                        }

                        return [
                            'name' => trim((string) ($item['name'] ?? basename($path))) ?: basename($path),
                            'preview_url' => route('landing.gallery.preview', ['path' => $path]),
                        ];
                    })
                    ->filter()
                    ->values(),
                'video_url' => $setting->video_url,
                'cta_badge' => $setting->cta_badge,
                'cta_label' => $setting->cta_label,
                'cta_url' => $setting->cta_url,
                'faq_items' => $setting->faq_items ?? [],
            ] : null,
            'kategoriLanding' => $kategori,
            'galleryLanding' => collect($setting?->gallery_items ?? [])
                ->map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $path = trim((string) ($item['path'] ?? ''));
                    if ($path === '') {
                        return null;
                    }

                    return [
                        'name' => trim((string) ($item['name'] ?? basename($path))) ?: basename($path),
                        'preview_url' => route('landing.gallery.preview', ['path' => $path]),
                    ];
                })
                ->filter()
                ->values(),
            'timelineLanding' => $timeline,
        ]);
    }

    public function panduan()
    {
        $setting = LandingSetting::query()->first();
        $edisi = null;
        if ($setting?->landing_edisi_lomba_id) {
            $edisi = Edition::query()->find($setting->landing_edisi_lomba_id);
        }
        $edisi = $edisi
            ?? Edition::query()
                ->orderByDesc('tahun')
                ->get(['id', 'nama', 'tahun', 'status', 'aktif'])
                ->firstWhere('id', (int) session('edisi_aktif_id'))
            ?? Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        $kategori = collect();
        $ketentuan = [];
        $templateProposal = null;

        if ($edisi) {
            $kategoriRaw = KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'slug', 'deskripsi', 'icon_path']);

            $bobotMap = BobotPenilaianKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->get()
                ->keyBy('kategori_lomba_id');

            $kategori = $kategoriRaw->map(function (KategoriLomba $item) use ($bobotMap) {
                $bobot = $bobotMap->get($item->id);
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'slug' => $item->slug,
                    'deskripsi' => $item->deskripsi,
                    'icon_url' => $item->icon_path ? route('kategori.icon.preview', ['kategori' => $item->id]) : null,
                    'weights' => $this->decodeKriteria($bobot?->catatan),
                ];
            })->values();

            $panduan = PanduanLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->first();

            $ketentuan = $this->decodeKetentuan($panduan?->ketentuan_umum);
            if ($panduan?->template_proposal_path) {
                $templateProposal = [
                    'nama' => $panduan->template_proposal_nama_tampil,
                    'url' => $panduan->template_proposal_path,
                ];
            }
        }

        return Inertia::render('Landing/Panduan', [
            'kategoriLanding' => $kategori,
            'ketentuanLanding' => $ketentuan,
            'templateProposal' => $templateProposal,
        ]);
    }

    public function pameran()
    {
        return Inertia::render('Landing/Pameran');
    }

    public function gallery()
    {
        $setting = LandingSetting::query()->first();

        $galleryItems = collect($setting?->gallery_items ?? [])
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $path = trim((string) ($item['path'] ?? ''));
                if ($path === '') {
                    return null;
                }

                return [
                    'name' => trim((string) ($item['name'] ?? basename($path))) ?: basename($path),
                    'preview_url' => route('landing.gallery.preview', ['path' => $path]),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Landing/Gallery', [
            'galleryItems' => $galleryItems,
        ]);
    }

    public function nominate()
    {
        $daftarEdisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status', 'aktif']);

        $timelinePengumumanPerEdisi = $daftarEdisi
            ->mapWithKeys(function ($edisi) {
                $timelinePengumuman = TimelineLomba::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('fase_kunci', 'pengumuman_nominasi')
                    ->where('aktif', true)
                    ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('mulai_pada')
                    ->orderBy('id')
                    ->first();

                return [
                    $edisi->id => $timelinePengumuman ? [
                        'judul' => $timelinePengumuman->judul,
                        'mulai_pada' => $timelinePengumuman->mulai_pada?->toIso8601String(),
                        'selesai_pada' => $timelinePengumuman->selesai_pada?->toIso8601String(),
                        'is_tba' => (bool) $timelinePengumuman->is_tba,
                    ] : null,
                ];
            })
            ->all();

        $kategoriOptions = KategoriLomba::query()
            ->whereIn('edisi_lomba_id', $daftarEdisi->pluck('id'))
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama', 'edisi_lomba_id', 'icon_path'])
            ->map(function (KategoriLomba $item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'edisi_lomba_id' => $item->edisi_lomba_id,
                    'icon_url' => $item->icon_path
                        ? route('kategori.icon.preview', ['kategori' => $item->id])
                        : null,
                ];
            })
            ->values();

        $nominasi = KaryaPeserta::query()
            ->with(['edisi:id,nama,tahun', 'kategori:id,nama'])
            ->where('lolos_nominasi', true)
            ->where('status', 'submitted')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KaryaPeserta $item) {
                $anggota = collect($item->anggota_tim ?? [])
                    ->map(function ($row) {
                        if (!is_array($row)) {
                            return ['nama' => '-', 'nim' => '-'];
                        }
                        return [
                            'nama' => $row['nama'] ?? '-',
                            'nim' => $row['nim'] ?? '-',
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $item->id,
                    'edisi_id' => $item->edisi_lomba_id,
                    'tahun' => $item->edisi?->tahun,
                    'nama_edisi' => $item->edisi?->nama,
                    'kategori_id' => $item->kategori_lomba_id,
                    'kategori' => $item->nama_kategori ?? $item->kategori?->nama,
                    'icon_url' => $item->kategori?->icon_path
                        ? route('kategori.icon.preview', ['kategori' => $item->kategori->id])
                        : null,
                    'nama_karya' => $item->nama_karya,
                    'ringkasan' => $item->pameran_ringkasan,
                    'anggota_tim' => $anggota,
                ];
            })
            ->values();

        return Inertia::render('Landing/Nominate', [
            'daftarEdisi' => $daftarEdisi,
            'edisiDefault' => $daftarEdisi->firstWhere('status', 'aktif')?->id
                ?? $daftarEdisi->firstWhere('aktif', true)?->id
                ?? $daftarEdisi->firstWhere('id', (int) session('edisi_aktif_id'))?->id
                ?? $daftarEdisi->first()?->id,
            'kategoriOptions' => $kategoriOptions,
            'nominasi' => $nominasi,
            'timelinePengumumanPerEdisi' => $timelinePengumumanPerEdisi,
        ]);
    }

    public function juara()
    {
        return Inertia::render('Landing/Juara', $this->winnersPayload());
    }

    public function juaraDetail(string $id)
    {
        $winner = PemenangKarya::query()
            ->with([
                'karya:id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_path,pameran_logo_nama_asli,pameran_logo_mime_type,pameran_submitted_at',
                'kategori:id,nama',
                'edisi:id,nama,tahun',
            ])
            ->whereKey($id)
            ->first();

        if (!$winner) {
            return redirect()->route('landing.juara');
        }

        return Inertia::render('Landing/JuaraDetail', [
            'winner' => $this->mapWinnerRow($winner),
            'edisi' => $winner->edisi ? [
                'id' => $winner->edisi->id,
                'nama' => $winner->edisi->nama,
                'tahun' => $winner->edisi->tahun,
            ] : null,
        ]);
    }

    public function previewJuaraLogo(KaryaPeserta $karya): StreamedResponse
    {
        abort_unless($karya->pameran_logo_path, 404);
        abort_unless(Storage::disk('public')->exists($karya->pameran_logo_path), 404);

        return Storage::disk('public')->response(
            $karya->pameran_logo_path,
            $karya->pameran_logo_nama_asli ?? 'logo',
            ['Content-Type' => $karya->pameran_logo_mime_type ?: 'application/octet-stream']
        );
    }

    public function previewMotionGraphicVideo(): StreamedResponse
    {
        $setting = LandingSetting::query()->first();
        abort_unless($setting?->video_path, 404);
        abort_unless(Storage::disk('public')->exists($setting->video_path), 404);

        return Storage::disk('public')->response(
            $setting->video_path,
            basename($setting->video_path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($setting->video_path) ?: 'application/octet-stream',
            ]
        );
    }

    public function previewCarouselImage(string $path): StreamedResponse
    {
        $decodedPath = urldecode($path);
        abort_unless($decodedPath, 404);
        abort_unless(Storage::disk('public')->exists($decodedPath), 404);

        $mimeType = Storage::disk('public')->mimeType($decodedPath) ?: 'application/octet-stream';
        $stream = Storage::disk('public')->readStream($decodedPath);

        abort_unless($stream !== false, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($decodedPath) . '"',
        ]);
    }

    public function previewGalleryImage(string $path): StreamedResponse
    {
        $decodedPath = urldecode($path);
        abort_unless($decodedPath, 404);
        abort_unless(Storage::disk('public')->exists($decodedPath), 404);

        $mimeType = Storage::disk('public')->mimeType($decodedPath) ?: 'application/octet-stream';
        $stream = Storage::disk('public')->readStream($decodedPath);

        abort_unless($stream !== false, 404);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($decodedPath) . '"',
        ]);
    }
}
