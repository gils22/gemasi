<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\KaryaPeserta;
use App\Models\PemenangKarya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class JuaraController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $pemenang = PemenangKarya::query()
            ->with([
                'karya:id,user_id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_nama_asli,pameran_logo_path,pameran_submitted_at',
                'kategori:id,nama',
                'edisi:id,nama,tahun,status,aktif',
            ])
            ->whereHas('karya', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('edisi_lomba_id')
            ->orderBy('kategori_lomba_id')
            ->orderBy('peringkat')
            ->get()
            ->map(function (PemenangKarya $row) {
                $anggota = collect($row->karya?->anggota_tim ?? [])
                    ->map(function ($item) {
                        $nama = is_array($item) ? ($item['nama'] ?? '-') : '-';
                        $nim = is_array($item) ? ($item['nim'] ?? '-') : '-';
                        return ['nama' => $nama, 'nim' => $nim];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $row->id,
                    'karya_id' => $row->karya_peserta_id,
                    'edisi_id' => $row->edisi_lomba_id,
                    'edisi_label' => $row->edisi ? ($row->edisi->nama . ' (' . $row->edisi->tahun . ')') : null,
                    'peringkat' => $row->peringkat,
                    'nama_karya' => $row->karya?->nama_karya,
                    'nama_kategori' => $row->karya?->nama_kategori ?? $row->kategori?->nama,
                    'nilai_final' => $row->nilai_final,
                    'anggota_tim' => $anggota,
                    'pameran_ringkasan' => $row->karya?->pameran_ringkasan,
                    'pameran_link_video' => $row->karya?->pameran_link_video,
                    'pameran_logo_name' => $row->karya?->pameran_logo_nama_asli,
                    'pameran_logo_url' => $row->karya?->pameran_logo_path
                        ? route('peserta.pameran.logo.preview', ['karya' => $row->karya->id])
                        : null,
                    'pameran_submitted_at' => $row->karya?->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })
            ->values();

        return Inertia::render('Peserta/Juara/Index', [
            'pemenang' => $pemenang,
            'bolehEdit' => true,
            'batasPengumpulan' => null,
        ]);
    }

    public function update(Request $request, KaryaPeserta $karya)
    {
        abort_unless((int) $karya->user_id === (int) $request->user()->id, 403);

        $isWinner = PemenangKarya::query()
            ->where('edisi_lomba_id', $karya->edisi_lomba_id)
            ->where('karya_peserta_id', $karya->id)
            ->exists();
        abort_unless($isWinner, 403, 'Karya ini tidak termasuk juara.');

        $edisiId = (int) $karya->edisi_lomba_id;

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
            $logoPath = $logo->store("pameran-karya/{$edisiId}/{$request->user()->id}/logo", 'public');
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

        return redirect()->back()->with('success', 'Data juara berhasil diperbarui.');
    }
}
