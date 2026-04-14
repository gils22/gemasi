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
use Inertia\Inertia;

class LandingSettingController extends Controller
{
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
                if ($nama === '') {
                    return null;
                }
                return ['nama' => $nama, 'poin' => $poin];
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
                ->orderBy('urutan')
                ->get(['id', 'nama', 'slug', 'deskripsi', 'aktif']);

            $timeline = TimelineLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->orderBy('urutan')
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
            'cta_badge' => 'nullable|string|max:255',
            'cta_label' => 'nullable|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'faq_items' => 'nullable|array',
            'faq_items.*.q' => 'required_with:faq_items|string|max:255',
            'faq_items.*.a' => 'required_with:faq_items|string|max:1000',
        ]);

        $setting = LandingSetting::query()->firstOrCreate(['id' => 1]);
        $setting->update([
            'landing_edisi_lomba_id' => $data['landing_edisi_lomba_id'] ?? null,
            'hero_badge' => $data['hero_badge'] ?? null,
            'hero_title' => $data['hero_title'] ?? null,
            'hero_subtitle' => $data['hero_subtitle'] ?? null,
            'about_text' => $data['about_text'] ?? null,
            'cta_badge' => $data['cta_badge'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'faq_items' => $data['faq_items'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Pengaturan landing diperbarui.');
    }
}
