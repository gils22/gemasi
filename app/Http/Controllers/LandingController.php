<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\PemenangArsip;
use App\Models\PemenangKarya;
use Inertia\Inertia;

class LandingController extends Controller
{
    private function winnersPayload(): array
    {
        $edisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun', 'status']);

        $pemenang = PemenangKarya::query()
            ->with(['karya:id,nama_karya,nama_kategori,anggota_tim', 'kategori:id,nama', 'edisi:id,nama,tahun'])
            ->orderByDesc('edisi_lomba_id')
            ->orderBy('kategori_lomba_id')
            ->orderBy('peringkat')
            ->get()
            ->map(function ($row) {
                $anggota = collect($row->karya?->anggota_tim ?? [])
                    ->map(function ($item) {
                        $nama = is_array($item) ? ($item['nama'] ?? '-') : '-';
                        $nim = is_array($item) ? ($item['nim'] ?? '-') : '-';
                        return ['nama' => $nama, 'nim' => $nim];
                    })
                    ->values()
                    ->all();

                return [
                    'edisi_id' => $row->edisi_lomba_id,
                    'tahun' => $row->edisi?->tahun,
                    'nama_edisi' => $row->edisi?->nama,
                    'peringkat' => $row->peringkat,
                    'kategori' => $row->karya?->nama_kategori ?? $row->kategori?->nama,
                    'nama_karya' => $row->karya?->nama_karya,
                    'anggota_tim' => $anggota,
                ];
            })
            ->values();

        $pemenangArsip = PemenangArsip::query()
            ->with('edisi:id,nama,tahun')
            ->orderByDesc('edisi_lomba_id')
            ->orderBy('kategori')
            ->orderBy('peringkat')
            ->get()
            ->map(function (PemenangArsip $row) {
                return [
                    'edisi_id' => $row->edisi_lomba_id,
                    'tahun' => $row->edisi?->tahun,
                    'nama_edisi' => $row->edisi?->nama,
                    'peringkat' => $row->peringkat,
                    'kategori' => $row->kategori,
                    'nama_karya' => $row->nama_karya,
                    'anggota_tim' => $row->anggota_tim ?? [],
                ];
            })
            ->values();

        $gabungan = $pemenang
            ->concat($pemenangArsip)
            ->groupBy('edisi_id')
            ->map(function ($items) {
                return $items->sortBy([
                    ['kategori', 'asc'],
                    ['peringkat', 'asc'],
                ])->values();
            })
            ->all();

        return [
            'daftarEdisi' => $edisi,
            'pemenangPerEdisi' => $gabungan,
        ];
    }

    public function index()
    {
        return Inertia::render('Landing/Index');
    }

    public function panduan()
    {
        return Inertia::render('Landing/Panduan');
    }

    public function pameran()
    {
        return Inertia::render('Landing/Pameran');
    }

    public function nominate()
    {
        return Inertia::render('Landing/Nominate');
    }

    public function juara()
    {
        return Inertia::render('Landing/Juara', $this->winnersPayload());
    }
}
