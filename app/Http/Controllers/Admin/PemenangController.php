<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\PemenangArsip;
use App\Models\PemenangKarya;
use App\Models\PenilaianTahapDua;
use Illuminate\Http\Request;
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

        $daftarEdisi = Edition::query()
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun']);

        $pemenang = PemenangKarya::query()
            ->with(['karya:id,nama_karya,nama_kategori,anggota_tim', 'kategori:id,nama'])
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
                $anggota = collect($row->anggota_tim ?? [])
                    ->map(function ($item) {
                        $nama = is_array($item) ? ($item['nama'] ?? '-') : '-';
                        $nim = is_array($item) ? ($item['nim'] ?? '-') : '-';
                        return ['nama' => $nama, 'nim' => $nim];
                    })
                    ->values()
                    ->all();
                return [
                    'id' => $row->id,
                    'edisi_id' => $row->edisi_lomba_id,
                    'tahun' => $row->edisi?->tahun,
                    'nama_edisi' => $row->edisi?->nama,
                    'peringkat' => $row->peringkat,
                    'nama_kategori' => $row->kategori,
                    'nama_karya' => $row->nama_karya,
                    'anggota_tim' => $anggota,
                ];
            })
            ->values();

        return Inertia::render('Admin/Pemenang/Index', [
            'pemenang' => $pemenang,
            'pemenangArsip' => $pemenangArsip,
            'daftarEdisi' => $daftarEdisi,
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

        return redirect()->back()->with('success', 'Pemenang berhasil ditetapkan.');
    }

    public function storeArsip(Request $request)
    {
        $validated = $request->validate([
            'edisi_lomba_id' => 'required|integer|exists:edisi_lomba,id',
            'kategori' => 'required|string|max:255',
            'peringkat' => 'required|integer|min:1|max:3',
            'nama_karya' => 'required|string|max:255',
            'anggota_tim' => 'nullable|array',
            'anggota_tim.*.nama' => 'required_with:anggota_tim|string|max:255',
            'anggota_tim.*.nim' => 'required_with:anggota_tim|string|max:50',
        ]);

        PemenangArsip::query()->create([
            'edisi_lomba_id' => $validated['edisi_lomba_id'],
            'kategori' => $validated['kategori'],
            'peringkat' => $validated['peringkat'],
            'nama_karya' => $validated['nama_karya'],
            'anggota_tim' => $validated['anggota_tim'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Pemenang arsip berhasil ditambahkan.');
    }

    public function updateArsip(Request $request, PemenangArsip $arsip)
    {
        $validated = $request->validate([
            'edisi_lomba_id' => 'required|integer|exists:edisi_lomba,id',
            'kategori' => 'required|string|max:255',
            'peringkat' => 'required|integer|min:1|max:3',
            'nama_karya' => 'required|string|max:255',
            'anggota_tim' => 'nullable|array',
            'anggota_tim.*.nama' => 'required_with:anggota_tim|string|max:255',
            'anggota_tim.*.nim' => 'required_with:anggota_tim|string|max:50',
        ]);

        $arsip->update([
            'edisi_lomba_id' => $validated['edisi_lomba_id'],
            'kategori' => $validated['kategori'],
            'peringkat' => $validated['peringkat'],
            'nama_karya' => $validated['nama_karya'],
            'anggota_tim' => $validated['anggota_tim'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Pemenang arsip diperbarui.');
    }

    public function destroyArsip(PemenangArsip $arsip)
    {
        $arsip->delete();

        return redirect()->back()->with('success', 'Pemenang arsip dihapus.');
    }
}
