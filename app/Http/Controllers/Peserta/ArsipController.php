<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\KaryaPeserta;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $arsipKarya = KaryaPeserta::query()
            ->with('edisi')
            ->where('user_id', $userId)
            ->whereHas('edisi', fn ($query) => $query->where('status', 'arsip'))
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KaryaPeserta $item) {
                $anggota = collect($item->anggota_tim ?? []);
                $ketua = $anggota->firstWhere('peran', 'ketua');

                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'jumlah_anggota_tim' => $anggota->count(),
                    'nama_ketua' => $ketua['nama'] ?? null,
                    'status_tampilan' => $item->status === 'submitted'
                        ? 'Lengkap'
                        : 'Tahap 1 tersimpan',
                    'updated_at' => optional($item->updated_at)->format('d M Y, H:i'),
                    'edisi_label' => optional($item->edisi)->nama ?? '-',
                ];
            })
            ->values();

        $arsipPameran = KaryaPeserta::query()
            ->with('edisi')
            ->where('user_id', $userId)
            ->where('lolos_nominasi', true)
            ->whereHas('edisi', fn ($query) => $query->where('status', 'arsip'))
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (KaryaPeserta $item) {
                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'edisi_label' => optional($item->edisi)->nama ?? '-',
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

        return Inertia::render('Peserta/Arsip/Index', [
            'arsipKarya' => $arsipKarya,
            'arsipPameran' => $arsipPameran,
        ]);
    }
}
