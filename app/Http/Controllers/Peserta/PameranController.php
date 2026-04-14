<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\TimelineLomba;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PameranController extends Controller
{
    private function resolveEdisiAktifOrFail(): Edition
    {
        $tahunSekarang = (int) now()->format('Y');
        $edisi = Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->where('tahun', $tahunSekarang)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        abort_if(!$edisi, 500, 'Edisi aktif belum tersedia.');
        session(['edisi_aktif_id' => $edisi->id]);

        return $edisi;
    }

    private function pameranMasihDibuka(Edition $edisi): array
    {
        $aktif = ($edisi->status === 'aktif') || (bool) $edisi->aktif;
        if (!$aktif) {
            return [false, null];
        }

        $timelinePameran = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('fase_kunci', 'pameran_karya')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get(['mulai_pada', 'selesai_pada', 'is_tba']);

        if ($timelinePameran->isEmpty()) {
            return [true, null];
        }

        $now = now();
        $batas = null;

        foreach ($timelinePameran as $item) {
            if ((bool) $item->is_tba) {
                return [true, null];
            }

            $mulai = $item->mulai_pada ? Carbon::parse($item->mulai_pada) : null;
            $selesai = $item->selesai_pada ? Carbon::parse($item->selesai_pada) : null;
            if ($selesai) {
                $batas = $selesai;
            }

            if (!$mulai && !$selesai) {
                return [true, $batas];
            }

            if ($mulai && !$selesai && $now->gte($mulai)) {
                return [true, $batas];
            }

            if (!$mulai && $selesai && $now->lte($selesai)) {
                return [true, $batas];
            }

            if ($mulai && $selesai && $now->betweenIncluded($mulai, $selesai)) {
                return [true, $batas];
            }
        }

        return [false, $batas];
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        [$bolehEdit, $batas] = $this->pameranMasihDibuka($edisi);
        $nominasi = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('user_id', (int) $request->user()->id)
            ->where('lolos_nominasi', true)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KaryaPeserta $item) {
                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'anggota_tim' => $item->anggota_tim,
                    'pameran_logo_name' => $item->pameran_logo_nama_asli,
                    'pameran_logo_url' => $item->pameran_logo_path
                        ? route('peserta.pameran.logo.preview', ['karya' => $item->id])
                        : null,
                    'pameran_link_video' => $item->pameran_link_video,
                    'pameran_ringkasan' => $item->pameran_ringkasan,
                    'pameran_submitted_at' => $item->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })
            ->values();

        return Inertia::render('Peserta/Pameran/Index', [
            'nominasi' => $nominasi,
            'edisiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'bolehEdit' => $bolehEdit,
            'batasPengumpulan' => $batas?->format('d M Y, H:i'),
        ]);
    }

    public function update(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        [$bolehEdit] = $this->pameranMasihDibuka($edisi);
        abort_unless($bolehEdit, 403, 'Pengumpulan pameran sudah ditutup.');
        abort_unless((int) $karya->user_id === (int) $request->user()->id, 403);
        abort_unless((int) $karya->edisi_lomba_id === (int) $edisi->id, 403);
        abort_unless($karya->lolos_nominasi, 403);

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
            $logoPath = $logo->store("pameran-karya/{$edisi->id}/{$request->user()->id}/logo", 'public');
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

        return redirect()->back()->with('success', 'Data pameran berhasil disimpan.');
    }

    public function previewLogo(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless((int) $karya->user_id === (int) $request->user()->id, 403);
        abort_unless((int) $karya->edisi_lomba_id === (int) $edisi->id, 403);
        abort_unless($karya->lolos_nominasi, 403);
        abort_unless($karya->pameran_logo_path, 404);
        abort_unless(Storage::disk('public')->exists($karya->pameran_logo_path), 404);

        return Storage::disk('public')->response(
            $karya->pameran_logo_path,
            $karya->pameran_logo_nama_asli ?? 'logo',
            ['Content-Type' => $karya->pameran_logo_mime_type ?: 'application/octet-stream']
        );
    }
}
