<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        $karya = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('user_id', (int) $request->user()->id)
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get();

        $submissionCount = $karya->where('status', 'submitted')->count();
        $nominasi = $karya->where('lolos_nominasi', true)->values();

        return Inertia::render('Peserta/Dashboard', [
            'edisiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'statusTim' => $karya->isEmpty()
                ? 'Belum Terdaftar'
                : ($submissionCount > 0 ? 'Submission Lengkap' : 'Draft Tersimpan'),
            'submissionCount' => $submissionCount,
            'nominasiCount' => $nominasi->count(),
            'nominasiList' => $nominasi->map(function (KaryaPeserta $item) {
                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'pameran_logo_url' => $item->pameran_logo_path
                        ? route('peserta.pameran.logo.preview', ['karya' => $item->id])
                        : null,
                    'pameran_submitted_at' => $item->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })->values(),
        ]);
    }
}
