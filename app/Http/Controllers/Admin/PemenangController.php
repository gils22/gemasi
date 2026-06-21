<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\PemenangFavorit;
use App\Models\PemenangKarya;
use App\Models\PenilaianTahapDua;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PemenangController extends Controller
{
    private function resolveEdisiKonteks(): Edition
    {
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

    private function buildRanking(Edition $edisi): array
    {
        $karyaNominasi = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->get(['id', 'nama_karya', 'nama_kategori', 'kategori_lomba_id']);

        $penilaian = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->get(['karya_peserta_id', 'total_nilai']);

        $rekap = $karyaNominasi->map(function (KaryaPeserta $karya) use ($penilaian) {
            $rows = $penilaian->where('karya_peserta_id', $karya->id);
            $count = $rows->count();
            $avg = $count > 0 ? round((float) $rows->avg('total_nilai'), 2) : null;
            return [
                'id' => $karya->id,
                'nama_karya' => $karya->nama_karya,
                'nama_kategori' => $karya->nama_kategori,
                'kategori_lomba_id' => $karya->kategori_lomba_id,
                'rata_rata' => $avg,
            ];
        })->filter(fn ($row) => $row['rata_rata'] !== null)->values();

        return $rekap
            ->groupBy('kategori_lomba_id')
            ->map(function ($rows) {
                return $rows->sortByDesc('rata_rata')->values()->all();
            })
            ->all();
    }

    public function index()
    {
        $edisi = $this->resolveEdisiKonteks();

        $pemenang = PemenangKarya::query()
            ->with([
                'karya:id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_nama_asli,pameran_logo_path,pameran_submitted_at',
                'kategori:id,nama',
            ])
            ->where('edisi_lomba_id', $edisi->id)
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
                    'peringkat' => $row->peringkat,
                    'nama_karya' => $row->karya?->nama_karya,
                    'nama_kategori' => $row->karya?->nama_kategori ?? $row->kategori?->nama,
                    'nilai_final' => $row->nilai_final,
                    'anggota_tim' => $anggota,
                    'pameran_ringkasan' => $row->karya?->pameran_ringkasan,
                    'pameran_link_video' => $row->karya?->pameran_link_video,
                    'pameran_logo_name' => $row->karya?->pameran_logo_nama_asli,
                    'pameran_logo_url' => $row->karya?->pameran_logo_path
                        ? route('admin.pameran.logo.preview', ['karya' => $row->karya->id])
                        : null,
                    'pameran_submitted_at' => $row->karya?->pameran_submitted_at?->format('d M Y, H:i'),
                ];
            })
            ->values();

        $favorit = PemenangFavorit::query()
            ->with([
                'karya:id,nama_karya,nama_kategori,anggota_tim,pameran_ringkasan,pameran_link_video,pameran_logo_nama_asli,pameran_logo_path,pameran_submitted_at,status,lolos_nominasi',
            ])
            ->where('edisi_lomba_id', $edisi->id)
            ->orderBy('peringkat')
            ->get();

        $karyaNominasi = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->orderBy('nama_karya')
            ->get(['id', 'nama_karya', 'nama_kategori']);

        $favoritOptions = $karyaNominasi
            ->map(fn (KaryaPeserta $karya) => [
                'id' => $karya->id,
                'label' => ($karya->nama_karya ?? '-') . ' — ' . ($karya->nama_kategori ?? '-'),
            ])
            ->values();

        return Inertia::render('Admin/Pemenang/Index', [
            'pemenang' => $pemenang,
            'favorit' => [
                'items' => $favorit->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'peringkat' => $item->peringkat,
                        'karya_peserta_id' => $item->karya_peserta_id,
                        'nama_karya' => $item->karya?->nama_karya,
                        'nama_kategori' => $item->karya?->nama_kategori,
                        'anggota_tim' => collect($item->karya?->anggota_tim ?? [])
                            ->map(function ($anggota) {
                                return [
                                    'nama' => is_array($anggota) ? ($anggota['nama'] ?? '-') : '-',
                                    'nim' => is_array($anggota) ? ($anggota['nim'] ?? '-') : '-',
                                ];
                            })
                            ->values()
                            ->all(),
                        'pameran_ringkasan' => $item->karya?->pameran_ringkasan,
                        'pameran_link_video' => $item->karya?->pameran_link_video,
                        'pameran_logo_name' => $item->karya?->pameran_logo_nama_asli,
                        'pameran_logo_url' => $item->karya?->pameran_logo_path
                            ? route('admin.pameran.logo.preview', ['karya' => $item->karya->id])
                            : null,
                        'pameran_submitted_at' => $item->karya?->pameran_submitted_at?->format('d M Y, H:i'),
                    ];
                })->values()->all(),
            ],
            'favoritOptions' => $favoritOptions,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }

    public function tetapkan(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks();

        $ranking = $this->buildRanking($edisi);

        PemenangKarya::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->delete();

        $payload = [];
        foreach ($ranking as $kategoriId => $rows) {
            $top = array_slice($rows, 0, 3);
            foreach ($top as $index => $row) {
                $payload[] = [
                    'edisi_lomba_id' => $edisi->id,
                    'kategori_lomba_id' => $kategoriId,
                    'karya_peserta_id' => $row['id'],
                    'peringkat' => $index + 1,
                    'nilai_final' => $row['rata_rata'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($payload)) {
            PemenangKarya::query()->insert($payload);
        }

        return redirect()->back()->with('success', 'Pemenang edisi aktif berhasil ditetapkan.');
    }

    public function tetapkanFavorit(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks();

        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:5'],
            'karya_peserta_ids' => ['required', 'array'],
            'karya_peserta_ids.*' => [
                'required',
                'integer',
                Rule::exists('karya_peserta', 'id')->where(function ($query) use ($edisi) {
                    $query->where('edisi_lomba_id', $edisi->id)
                        ->where('status', 'submitted')
                        ->where('lolos_nominasi', true);
                }),
            ],
        ]);

        $ids = collect($validated['karya_peserta_ids'])
            ->filter()
            ->unique()
            ->take((int) $validated['jumlah'])
            ->values();

        abort_if($ids->isEmpty(), 422, 'Pilih minimal satu karya favorit.');

        PemenangFavorit::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->delete();

        PemenangFavorit::query()->insert(
            $ids->map(function ($karyaId, $index) use ($edisi) {
                return [
                    'edisi_lomba_id' => $edisi->id,
                    'peringkat' => $index + 1,
                    'karya_peserta_id' => (int) $karyaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all(),
        );

        return redirect()->back()->with('success', 'Karya favorit berhasil ditetapkan.');
    }

    // Arsip pemenang dihapus dari UI.
}
