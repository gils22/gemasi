<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\KategoriLomba;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

class KategoriLombaController extends Controller
{
    private function resolveEdisiKonteks(Request $request): Edition
    {
        $selectedId = (int) session('edisi_aktif_id');
        if ($selectedId > 0) {
            $bySession = Edition::query()->find($selectedId);
            if ($bySession) {
                return $bySession;
            }
        }

        return $this->resolveEdisiBerjalanOrFail();
    }

    private function resolveEdisiBerjalanOrFail(): Edition
    {
        $tahunSekarang = (int) now()->format('Y');

        $edisi = Edition::query()->where('status', 'aktif')->first();
        if (!$edisi) {
            $edisi = Edition::query()->where('aktif', true)->first();
        }
        if (!$edisi) {
            $edisi = Edition::query()->where('tahun', $tahunSekarang)->first();
        }
        if (!$edisi) {
            $edisi = Edition::query()->orderByDesc('tahun')->first();
        }

        abort_if(!$edisi, 500, 'Edisi lomba belum tersedia.');

        return $edisi;
    }

    private function ensureKategoriDalamEdisi(KategoriLomba $kategori, Edition $edisi): void
    {
        abort_if((int) $kategori->edisi_lomba_id !== (int) $edisi->id, 404);
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $isAdmin = $request->user()?->hasRole('admin') === true;
        $basePath = '/admin';
        $daftarEdisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status', 'aktif']);

        $kategori = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->orderBy('nama')
            ->get()
            ->map(fn (KategoriLomba $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'slug' => $item->slug,
                'deskripsi' => $item->deskripsi,
                'aktif' => (bool) $item->aktif,
                'created_at' => $item->created_at?->toISOString(),
                'icon_url' => $item->icon_path ? route('kategori.icon.preview', ['kategori' => $item->id]) : null,
                'icon_nama_asli' => $item->icon_nama_asli,
                'icon_ukuran' => $item->icon_ukuran,
            ])
            ->values();

        return Inertia::render('Kategori/Index', [
            'kategori' => $kategori,
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            // Admin tetap bisa mengelola data walaupun edisi yang dipilih berstatus arsip/draft.
            // Mode arsip hanya berlaku untuk role non-admin (jika suatu saat halaman ini dipakai lintas role).
            'modeArsip' => !$isAdmin && $edisi->status === 'arsip',
            'isEditable' => $isAdmin || $edisi->status === 'aktif',
            'isAdmin' => $isAdmin,
            'basePath' => $basePath,
            'daftarEdisi' => $daftarEdisi,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:2000',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'aktif' => 'nullable|boolean',
        ]);

        $nama = trim($validated['nama']);
        $slugDasar = Str::slug($nama);
        $slug = $slugDasar;
        $counter = 2;
        while (
            KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $slugDasar . '-' . $counter;
            $counter++;
        }

        $icon = $request->file('icon');
        $iconPath = null;
        $iconNamaAsli = null;
        $iconMimeType = null;
        $iconUkuran = null;
        if ($icon) {
            $iconPath = $icon->store('kategori-icons', 'public');
            $iconNamaAsli = $icon->getClientOriginalName();
            $iconMimeType = $icon->getClientMimeType();
            $iconUkuran = (int) $icon->getSize();
        }

        KategoriLomba::query()->create([
            'edisi_lomba_id' => $edisi->id,
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'icon_path' => $iconPath,
            'icon_nama_asli' => $iconNamaAsli,
            'icon_mime_type' => $iconMimeType,
            'icon_ukuran' => $iconUkuran,
            'aktif' => (bool) ($validated['aktif'] ?? true),
        ]);

        return redirect()->back()->setStatusCode(303);
    }

    public function previewIcon(Request $request, KategoriLomba $kategori): StreamedResponse
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureKategoriDalamEdisi($kategori, $edisi);
        abort_unless($kategori->icon_path, 404);
        abort_unless(Storage::disk('public')->exists($kategori->icon_path), 404);

        return Storage::disk('public')->response(
            $kategori->icon_path,
            $kategori->icon_nama_asli ?? basename($kategori->icon_path),
            [
                'Content-Type' => $kategori->icon_mime_type ?: Storage::disk('public')->mimeType($kategori->icon_path) ?: 'application/octet-stream',
            ]
        );
    }

    public function update(Request $request, KategoriLomba $kategori)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureKategoriDalamEdisi($kategori, $edisi);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:2000',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'aktif' => 'nullable|boolean',
        ]);

        $nama = trim($validated['nama']);
        $slugDasar = Str::slug($nama);
        $slug = $slugDasar;
        $counter = 2;
        while (
            KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('slug', $slug)
                ->where('id', '!=', $kategori->id)
                ->exists()
        ) {
            $slug = $slugDasar . '-' . $counter;
            $counter++;
        }

        $icon = $request->file('icon');
        $iconPath = $kategori->icon_path;
        $iconNamaAsli = $kategori->icon_nama_asli;
        $iconMimeType = $kategori->icon_mime_type;
        $iconUkuran = $kategori->icon_ukuran;
        if ($icon) {
            if ($kategori->icon_path) {
                Storage::disk('public')->delete($kategori->icon_path);
            }
            $iconPath = $icon->store('kategori-icons', 'public');
            $iconNamaAsli = $icon->getClientOriginalName();
            $iconMimeType = $icon->getClientMimeType();
            $iconUkuran = (int) $icon->getSize();
        }

        $kategori->update([
            'nama' => $nama,
            'slug' => $slug,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'icon_path' => $iconPath,
            'icon_nama_asli' => $iconNamaAsli,
            'icon_mime_type' => $iconMimeType,
            'icon_ukuran' => $iconUkuran,
            'aktif' => (bool) ($validated['aktif'] ?? true),
        ]);

        return redirect()->back()->setStatusCode(303);
    }

    public function destroy(Request $request, KategoriLomba $kategori)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureKategoriDalamEdisi($kategori, $edisi);
        if ($kategori->icon_path) {
            Storage::disk('public')->delete($kategori->icon_path);
        }

        $kategori->delete();

        return redirect()->back()->setStatusCode(303);
    }

    public function toggleAktif(Request $request, KategoriLomba $kategori)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);
        $this->ensureKategoriDalamEdisi($kategori, $edisi);

        $validated = $request->validate([
            'aktif' => 'required|boolean',
        ]);

        $kategori->update([
            'aktif' => (bool) $validated['aktif'],
        ]);

        return redirect()->back()->setStatusCode(303);
    }

    public function bulkDelete(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $edisi = $this->resolveEdisiKonteks($request);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:kategori_lomba,id',
        ]);

        KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()->back()->setStatusCode(303);
    }
}
