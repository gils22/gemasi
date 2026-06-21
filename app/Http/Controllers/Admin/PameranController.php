<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PameranController extends Controller
{
    private function resolveEdisiKonteks(Request $request): Edition
    {
        $user = $request->user();

        $query = Edition::query()->orderByDesc('tahun');

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

        $items = KaryaPeserta::query()
            ->with('peserta:id,name,email,avatar')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('lolos_nominasi', true)
            ->orderByDesc('pameran_submitted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KaryaPeserta $item) {
                $lengkap = (bool) ($item->pameran_logo_path && $item->pameran_link_video && $item->pameran_ringkasan);
                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'anggota_tim' => $item->anggota_tim ?? [],
                    'pameran_ringkasan' => $item->pameran_ringkasan,
                    'pameran_link_video' => $item->pameran_link_video,
                    'pameran_logo_name' => $item->pameran_logo_nama_asli,
                    'pameran_logo_url' => $item->pameran_logo_path
                        ? route('admin.pameran.logo.preview', ['karya' => $item->id])
                        : null,
                    'pameran_submitted_at' => $item->pameran_submitted_at?->format('d M Y, H:i'),
                    'pameran_lengkap' => $lengkap,
                    'peserta' => [
                        'name' => $item->peserta?->name,
                        'email' => $item->peserta?->email,
                        'avatar' => $item->peserta?->avatar,
                    ],
                ];
            })
            ->values();

        return Inertia::render('Admin/Pameran/Index', [
            'pameran' => $items,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }

    public function previewLogo(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless((int) $karya->edisi_lomba_id === (int) $edisi->id, 403);
        abort_unless($karya->pameran_logo_path, 404);
        abort_unless(Storage::disk('public')->exists($karya->pameran_logo_path), 404);

        return Storage::disk('public')->response(
            $karya->pameran_logo_path,
            $karya->pameran_logo_nama_asli ?? 'logo',
            ['Content-Type' => $karya->pameran_logo_mime_type ?: 'application/octet-stream']
        );
    }

    public function update(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless((int) $karya->edisi_lomba_id === (int) $edisi->id, 403);

        $validator = Validator::make($request->all(), [
            'pameran_logo' => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
            'pameran_link_video' => 'nullable|string|max:2048',
            'pameran_ringkasan' => 'nullable|string|max:2000',
        ]);

        $validated = $validator->validate();

        if ($request->hasFile('pameran_logo')) {
            if ($karya->pameran_logo_path) {
                Storage::disk('public')->delete($karya->pameran_logo_path);
            }

            $logo = $request->file('pameran_logo');
            $logoPath = $logo->store("pameran-karya/{$edisi->id}/admin/{$karya->id}/logo", 'public');
            $karya->pameran_logo_path = $logoPath;
            $karya->pameran_logo_nama_asli = $logo->getClientOriginalName();
            $karya->pameran_logo_mime_type = $logo->getClientMimeType();
            $karya->pameran_logo_ukuran = (int) $logo->getSize();
        }

        if (array_key_exists('pameran_link_video', $validated)) {
            $link = trim((string) $validated['pameran_link_video']);
            $karya->pameran_link_video = $link !== '' ? $link : null;
        }

        if (array_key_exists('pameran_ringkasan', $validated)) {
            $ringkasan = trim((string) $validated['pameran_ringkasan']);
            $karya->pameran_ringkasan = $ringkasan !== '' ? $ringkasan : null;
        }

        if ($request->hasFile('pameran_logo') || $validated['pameran_link_video'] ?? null || $validated['pameran_ringkasan'] ?? null) {
            $karya->pameran_submitted_at = now();
        }

        $karya->save();

        return redirect()->back()->with('success', 'Data pameran karya berhasil diperbarui.');
    }
}
