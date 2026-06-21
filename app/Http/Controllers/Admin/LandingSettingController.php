<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\BobotPenilaianKategori;
use App\Models\KategoriLomba;
use App\Models\LandingSetting;
use App\Models\PanduanLomba;
use App\Models\TimelineLomba;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

class LandingSettingController extends Controller
{
    private function normalizeCarouselItems($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $path = trim((string) ($item['path'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($path === '') {
                return null;
            }

            return [
                'path' => $path,
                'name' => $name !== '' ? $name : basename($path),
                'url' => Storage::disk('public')->url($path),
                'preview_url' => route('landing.carousel.preview', ['path' => $path]),
            ];
        }, $items)));
    }

    private function normalizeGalleryItems($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $path = trim((string) ($item['path'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($path === '') {
                return null;
            }

            return [
                'path' => $path,
                'name' => $name !== '' ? $name : basename($path),
                'url' => Storage::disk('public')->url($path),
                'preview_url' => route('landing.gallery.preview', ['path' => $path]),
            ];
        }, $items)));
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
                    'nama' => $nama,
                    'poin' => $poin,
                    'deskripsi' => $deskripsi,
                ];
            }, $decoded['kriteria'])));
        }

        return [];
    }

    private function resolveEdisiAktif(): ?Edition
    {
        return Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status', 'aktif'])
            ->firstWhere('id', (int) session('edisi_aktif_id'))
            ?? Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();
    }

    public function index()
    {
        $setting = LandingSetting::query()->first();
        $daftarEdisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status', 'aktif']);
        $edisi = null;
        if ($setting?->landing_edisi_lomba_id) {
            $edisi = $daftarEdisi->firstWhere(
                'id',
                (int) $setting->landing_edisi_lomba_id
            );
        }
        $edisi = $edisi ?? $this->resolveEdisiAktif();

        $kategori = collect();
        $timeline = collect();
        $bobotKategori = collect();
        $ketentuan = [];
        $templateProposal = null;
        if ($edisi) {
            $kategori = KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->orderBy('nama')
                ->get(['id', 'nama', 'slug', 'deskripsi', 'icon_path', 'aktif']);

            $timeline = TimelineLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
                ->orderBy('mulai_pada')
                ->orderBy('id')
                ->get(['id', 'judul', 'mulai_pada', 'selesai_pada', 'is_tba', 'aktif']);

            $bobotMap = BobotPenilaianKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->get()
                ->keyBy('kategori_lomba_id');

            $bobotKategori = $kategori->map(function ($item) use ($bobotMap) {
                $bobot = $bobotMap->get($item->id);
                return [
                    'kategori_lomba_id' => $item->id,
                    'nama_kategori' => $item->nama,
                    'icon_url' => $item->icon_path ? Storage::disk('public')->url($item->icon_path) : null,
                    'kriteria' => $this->decodeKriteria($bobot?->catatan),
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

        return Inertia::render('Admin/Landing/Index', [
            'landing' => $setting ? [
                'landing_edisi_lomba_id' => $setting->landing_edisi_lomba_id,
                'hero_badge' => $setting->hero_badge,
                'hero_title' => $setting->hero_title,
                'hero_subtitle' => $setting->hero_subtitle,
                'about_text' => $setting->about_text,
                'video_file_url' => $setting->video_path ? Storage::disk('public')->url($setting->video_path) : null,
                'video_file_name' => $setting->video_path ? basename($setting->video_path) : null,
                'video_url' => $setting->video_url,
                'login_carousel_items' => $this->normalizeCarouselItems($setting->login_carousel_items ?? []),
                'gallery_items' => $this->normalizeGalleryItems($setting->gallery_items ?? []),
                'cta_badge' => $setting->cta_badge,
                'cta_label' => $setting->cta_label,
                'cta_url' => $setting->cta_url,
                'faq_items' => $setting->faq_items ?? [],
            ] : null,
            'daftarEdisi' => $daftarEdisi,
            'edisiLanding' => $edisi ? [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ] : null,
            'kategoriLanding' => $kategori,
            'bobotLanding' => $bobotKategori,
            'ketentuanLanding' => $ketentuan,
            'templateProposal' => $templateProposal,
            'timelineLanding' => $timeline,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'landing_edisi_lomba_id' => 'nullable|integer|exists:edisi_lomba,id',
            'hero_badge' => 'nullable|string|max:255',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:5000',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg,mov|max:51200',
            'video_url' => 'nullable|string|max:255',
            'login_carousel_files' => 'nullable|array',
            'login_carousel_files.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'login_carousel_remove_paths' => 'nullable|array',
            'login_carousel_remove_paths.*' => 'string|max:255',
            'gallery_files' => 'nullable|array',
            'gallery_files.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'gallery_remove_paths' => 'nullable|array',
            'gallery_remove_paths.*' => 'string|max:255',
            'cta_badge' => 'nullable|string|max:255',
            'cta_label' => 'nullable|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'faq_items' => 'nullable|array',
            'faq_items.*.q' => 'required_with:faq_items|string|max:255',
            'faq_items.*.a' => 'required_with:faq_items|string|max:1000',
        ]);

        $setting = LandingSetting::query()->firstOrCreate(['id' => 1]);
        $carouselItems = $this->normalizeCarouselItems($setting->login_carousel_items ?? []);
        $galleryItems = $this->normalizeGalleryItems($setting->gallery_items ?? []);
        $removePaths = array_values(array_filter((array) ($data['login_carousel_remove_paths'] ?? [])));
        if ($removePaths) {
            $carouselItems = array_values(array_filter($carouselItems, function ($item) use ($removePaths) {
                return !in_array($item['path'] ?? '', $removePaths, true);
            }));

            foreach ($removePaths as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $loginCarouselFiles = array_values(array_filter(array_merge(
            (array) $request->file('login_carousel_files', []),
            (array) data_get($request->allFiles(), 'login_carousel_files', []),
        )));
        foreach ($loginCarouselFiles as $uploadedImage) {
            if (!$uploadedImage) {
                continue;
            }

            $path = $uploadedImage->store('landing-carousel', 'public');
            $carouselItems[] = [
                'path' => $path,
                'name' => $uploadedImage->getClientOriginalName(),
            ];
        }

        $galleryRemovePaths = array_values(array_filter((array) ($data['gallery_remove_paths'] ?? [])));
        if ($galleryRemovePaths) {
            $galleryItems = array_values(array_filter($galleryItems, function ($item) use ($galleryRemovePaths) {
                return !in_array($item['path'] ?? '', $galleryRemovePaths, true);
            }));

            foreach ($galleryRemovePaths as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $galleryFiles = array_values(array_filter(array_merge(
            (array) $request->file('gallery_files', []),
            (array) data_get($request->allFiles(), 'gallery_files', []),
        )));
        foreach ($galleryFiles as $uploadedImage) {
            if (!$uploadedImage) {
                continue;
            }

            $path = $uploadedImage->store('landing-gallery', 'public');
            $galleryItems[] = [
                'path' => $path,
                'name' => $uploadedImage->getClientOriginalName(),
            ];
        }

        if ($request->hasFile('video_file')) {
            if ($setting->video_path) {
                Storage::disk('public')->delete($setting->video_path);
            }

            $uploadedVideo = $request->file('video_file');
            $setting->video_path = $uploadedVideo->store('landing-video', 'public');
        }

        $setting->update([
            'landing_edisi_lomba_id' => $data['landing_edisi_lomba_id'] ?? null,
            'hero_badge' => $data['hero_badge'] ?? null,
            'hero_title' => $data['hero_title'] ?? null,
            'hero_subtitle' => $data['hero_subtitle'] ?? null,
            'about_text' => $data['about_text'] ?? null,
            'video_url' => $request->hasFile('video_file')
                ? null
                : ($data['video_url'] ?? null),
            'video_path' => $setting->video_path,
            'login_carousel_items' => $carouselItems,
            'gallery_items' => $galleryItems,
            'cta_badge' => $data['cta_badge'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'faq_items' => $data['faq_items'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Pengaturan landing diperbarui.');
    }

    public function previewVideo(): StreamedResponse
    {
        $setting = LandingSetting::query()->first();
        abort_unless($setting?->video_path, 404);
        abort_unless(Storage::disk('public')->exists($setting->video_path), 404);

        return Storage::disk('public')->response(
            $setting->video_path,
            basename($setting->video_path),
            ['Content-Type' => Storage::disk('public')->mimeType($setting->video_path) ?: 'application/octet-stream']
        );
    }

    public function previewGalleryImage(string $path): StreamedResponse
    {
        $decodedPath = urldecode($path);
        abort_unless($decodedPath, 404);
        abort_unless(Storage::disk('public')->exists($decodedPath), 404);

        return Storage::disk('public')->response(
            $decodedPath,
            basename($decodedPath),
            [
                'Content-Type' => Storage::disk('public')->mimeType($decodedPath) ?: 'application/octet-stream',
            ]
        );
    }
}
