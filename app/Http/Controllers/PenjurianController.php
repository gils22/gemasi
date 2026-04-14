<?php

namespace App\Http\Controllers;

use App\Models\BobotPenilaianKategori;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\LampiranKaryaPeserta;
use App\Models\PenilaianTahapDua;
use App\Models\PenugasanJuriKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PenjurianController extends Controller
{
    private function isPrivileged(Request $request): bool
    {
        $user = $request->user();
        return $user?->hasRole('admin') === true;
    }

    private function juriBolehNilaiKategori(Request $request, int $edisiId, ?int $kategoriId): bool
    {
        $user = $request->user();
        if ($this->isPrivileged($request)) {
            return true;
        }

        if (!$user->hasRole('juri') || !$kategoriId) {
            return false;
        }

        return PenugasanJuriKategori::query()
            ->where('edisi_lomba_id', $edisiId)
            ->where('kategori_lomba_id', $kategoriId)
            ->where('juri_id', (int) $user->id)
            ->whereIn('tahap', ['tahap_2', 'tahap_1_2'])
            ->exists();
    }

    private function resolveEdisiKonteks(Request $request): Edition
    {
        $user = $request->user();
        $prefix = $request->segment(1);

        $query = Edition::query()->orderByDesc('tahun');
        if ($prefix === 'juri') {
            $query->whereHas('roles', function ($q) use ($user) {
                $q->where('roles.name', 'juri')
                    ->where('edisi_lomba_user_role.user_id', $user->id);
            });
        }

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

    private function bolehAkses(Request $request, int $edisiId): bool
    {
        $user = $request->user();
        if ($this->isPrivileged($request)) {
            return true;
        }

        if ($user->hasRole('juri')) {
            return $user->hasRoleInEdition('juri', $edisiId);
        }

        return false;
    }

    private function decodeKriteria(?string $rawCatatan): array
    {
        $decoded = $rawCatatan ? json_decode($rawCatatan, true) : null;
        if (!is_array($decoded) || !isset($decoded['kriteria']) || !is_array($decoded['kriteria'])) {
            return [];
        }

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

    public function nominasi(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);

        $prefix = (string) $request->segment(1);
        $juriId = (int) $request->user()->id;
        $isAdmin = $this->isPrivileged($request);

        $penilaianGroupedAll = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->get(['karya_peserta_id', 'total_nilai', 'juri_id'])
            ->groupBy('karya_peserta_id');

        $penilaianGroupedSelf = $isAdmin
            ? $penilaianGroupedAll
            : PenilaianTahapDua::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('juri_id', $juriId)
                ->get(['karya_peserta_id', 'total_nilai', 'juri_id'])
                ->groupBy('karya_peserta_id');

        $karya = KaryaPeserta::query()
            ->with('peserta:id,name,email,avatar')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->when(!$this->isPrivileged($request), function ($q) use ($edisi, $juriId) {
                $assignedKategoriIds = PenugasanJuriKategori::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('juri_id', $juriId)
                    ->whereIn('tahap', ['tahap_2', 'tahap_1_2'])
                    ->pluck('kategori_lomba_id')
                    ->all();

                if (empty($assignedKategoriIds)) {
                    $q->whereRaw('1 = 0');
                    return;
                }

                $q->whereIn('kategori_lomba_id', $assignedKategoriIds);
            })
            ->orderBy('nama_kategori')
            ->orderBy('nama_karya')
            ->get()
            ->map(function (KaryaPeserta $item) use ($prefix, $penilaianGroupedAll, $penilaianGroupedSelf) {
                $rowsAll = $penilaianGroupedAll->get($item->id, collect());
                $countAll = $rowsAll->count();
                $avgAll = $countAll > 0 ? round((float) $rowsAll->avg('total_nilai'), 2) : null;
                $sumAll = $countAll > 0 ? round((float) $rowsAll->sum('total_nilai'), 2) : null;

                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'peserta' => [
                        'name' => $item->peserta?->name,
                        'email' => $item->peserta?->email,
                        'avatar' => $item->peserta?->avatar,
                    ],
                    'sudah_dinilai' => $countAll > 0,
                    'total_nilai' => $sumAll,
                    'rata_rata' => $avgAll,
                    'jumlah_penilai' => $countAll,
                    'url_nilai' => "/{$prefix}/penjurian/nilai/{$item->id}",
                    'url_detail' => "/{$prefix}/penjurian/detail/{$item->id}",
                ];
            })
            ->values();

        $kategoriOptions = $karya
            ->pluck('nama_kategori')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Penjurian/Nominasi', [
            'nominasi' => $karya,
            'kategoriOptions' => $kategoriOptions,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }

    public function nilai(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted' && (bool) $karya->lolos_nominasi, 404);
        abort_unless(
            $this->juriBolehNilaiKategori($request, (int) $edisi->id, (int) $karya->kategori_lomba_id),
            403
        );

        $karya->load('peserta:id,name,email,avatar');

        $bobot = BobotPenilaianKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('kategori_lomba_id', $karya->kategori_lomba_id)
            ->first();

        $kriteria = $this->decodeKriteria($bobot?->catatan);
        if (empty($kriteria)) {
            $prefix = (string) $request->segment(1);
            return redirect()
                ->route("{$prefix}.penjurian.nominasi")
                ->with('error', 'Kriteria bobot kategori belum diset pada guideline.')
                ->setStatusCode(303);
        }

        $assignedJuri = [];
        $targetJuriId = (int) $request->user()->id;
        if ($this->isPrivileged($request)) {
            $assignedJuri = PenugasanJuriKategori::query()
                ->with('juri:id,name,email')
                ->where('edisi_lomba_id', $edisi->id)
                ->where('kategori_lomba_id', $karya->kategori_lomba_id)
                ->whereIn('tahap', ['tahap_2', 'tahap_1_2'])
                ->get()
                ->map(fn (PenugasanJuriKategori $row) => [
                    'id' => (int) $row->juri_id,
                    'name' => $row->juri?->name,
                    'email' => $row->juri?->email,
                ])
                ->values()
                ->all();

            $requestedJuriId = (int) $request->query('juri_id', 0);
            if ($requestedJuriId > 0 && in_array($requestedJuriId, array_column($assignedJuri, 'id'), true)) {
                $targetJuriId = $requestedJuriId;
            } elseif (!empty($assignedJuri)) {
                $targetJuriId = (int) $assignedJuri[0]['id'];
            }

            $penilaianMap = PenilaianTahapDua::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('karya_peserta_id', $karya->id)
                ->get()
                ->keyBy('juri_id');

            $assignedJuri = array_map(function (array $row) use ($penilaianMap) {
                $penilaian = $penilaianMap->get($row['id']);
                return [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'status' => $penilaian ? 'Sudah dinilai' : 'Belum dinilai',
                    'total_nilai' => $penilaian ? (float) $penilaian->total_nilai : null,
                ];
            }, $assignedJuri);
        }

        $penilaian = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('karya_peserta_id', $karya->id)
            ->where('juri_id', $targetJuriId)
            ->first();

        $nilaiMap = collect($penilaian?->rincian_nilai ?? [])->keyBy('nama');
        $rows = collect($kriteria)->map(function (array $item) use ($nilaiMap) {
            $nilai = (float) ($nilaiMap->get($item['nama'])['nilai'] ?? 0);
            return [
                'nama' => $item['nama'],
                'bobot' => (float) $item['poin'],
                'nilai' => $nilai,
            ];
        })->values();

        return Inertia::render('Penjurian/Nilai', [
            'karya' => [
                'id' => $karya->id,
                'nama_karya' => $karya->nama_karya,
                'nama_kategori' => $karya->nama_kategori,
                'peserta' => [
                    'name' => $karya->peserta?->name,
                    'email' => $karya->peserta?->email,
                ],
            ],
            'kriteria' => $rows,
            'nilaiTersimpan' => $penilaian ? (float) $penilaian->total_nilai : null,
            'catatan' => $penilaian?->catatan,
            'juriOptions' => $assignedJuri,
            'selectedJuriId' => $this->isPrivileged($request) ? $targetJuriId : null,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }

    public function detail(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted' && (bool) $karya->lolos_nominasi, 404);
        abort_unless(
            $this->juriBolehNilaiKategori($request, (int) $edisi->id, (int) $karya->kategori_lomba_id),
            403
        );

        $prefix = (string) $request->segment(1);
        $karya->load(['peserta:id,name,email,avatar', 'lampiran:id,karya_peserta_id,nama_asli,deskripsi']);
        $penilaianRows = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('karya_peserta_id', $karya->id)
            ->get(['total_nilai']);
        $count = $penilaianRows->count();
        $avg = $count > 0 ? round((float) $penilaianRows->avg('total_nilai'), 2) : null;
        $sum = $count > 0 ? round((float) $penilaianRows->sum('total_nilai'), 2) : null;

        return response()->json([
            'karya' => [
                'id' => $karya->id,
                'nama_karya' => $karya->nama_karya,
                'nama_kategori' => $karya->nama_kategori,
                'peserta' => [
                    'name' => $karya->peserta?->name,
                    'email' => $karya->peserta?->email,
                    'avatar' => $karya->peserta?->avatar,
                ],
                'anggota_tim' => $karya->anggota_tim ?? [],
                'total_nilai' => $sum,
                'rata_rata' => $avg,
                'lampiran' => $karya->lampiran->map(fn (LampiranKaryaPeserta $lampiran) => [
                    'id' => $lampiran->id,
                    'nama' => $lampiran->nama_asli,
                    'deskripsi' => $lampiran->deskripsi,
                    'url' => "/{$prefix}/penjurian/lampiran/{$lampiran->id}/preview",
                ])->values(),
            ],
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }

    public function previewLampiran(Request $request, LampiranKaryaPeserta $lampiran)
    {
        $karya = $lampiran->karya;
        abort_unless($karya, 404);
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted' && (bool) $karya->lolos_nominasi, 404);
        abort_unless(
            $this->juriBolehNilaiKategori($request, (int) $edisi->id, (int) $karya->kategori_lomba_id),
            403
        );
        abort_unless(Storage::disk('public')->exists($lampiran->path_file), 404);

        return Storage::disk('public')->response(
            $lampiran->path_file,
            $lampiran->nama_asli,
            ['Content-Type' => $lampiran->mime_type ?: 'application/octet-stream']
        );
    }

    public function simpanNilai(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted' && (bool) $karya->lolos_nominasi, 404);
        abort_unless(
            $this->juriBolehNilaiKategori($request, (int) $edisi->id, (int) $karya->kategori_lomba_id),
            403
        );

        $validated = $request->validate([
            'kriteria' => 'required|array|min:1',
            'kriteria.*.nama' => 'required|string|max:255',
            'kriteria.*.bobot' => 'required|numeric|min:0|max:100',
            'kriteria.*.nilai' => 'required|numeric|min:0|max:100',
            'catatan' => 'nullable|string|max:4000',
            'juri_id' => 'nullable|integer|exists:users,id',
        ]);

        $targetJuriId = (int) $request->user()->id;
        if ($this->isPrivileged($request)) {
            $targetJuriId = (int) ($validated['juri_id'] ?? 0);
            if ($targetJuriId <= 0) {
                throw ValidationException::withMessages([
                    'juri_id' => 'Pilih juri untuk menyimpan nilai.',
                ]);
            }

            $validJuri = PenugasanJuriKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('kategori_lomba_id', $karya->kategori_lomba_id)
                ->where('juri_id', $targetJuriId)
                ->exists();
            if (!$validJuri) {
                throw ValidationException::withMessages([
                    'juri_id' => 'Juri tidak terdaftar untuk kategori ini.',
                ]);
            }
        }

        $bobot = BobotPenilaianKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('kategori_lomba_id', $karya->kategori_lomba_id)
            ->first();
        $kriteriaAsli = collect($this->decodeKriteria($bobot?->catatan));
        if ($kriteriaAsli->isEmpty()) {
            throw ValidationException::withMessages([
                'kriteria' => 'Kriteria bobot kategori belum diset pada guideline.',
            ]);
        }

        $namaAsli = $kriteriaAsli->pluck('nama')->values()->all();
        $namaInput = collect($validated['kriteria'])->pluck('nama')->values()->all();
        if ($namaAsli !== $namaInput) {
            throw ValidationException::withMessages([
                'kriteria' => 'Struktur kriteria tidak sesuai guideline kategori.',
            ]);
        }

        $rincian = collect($validated['kriteria'])->map(function (array $item) {
            $nilai = (float) $item['nilai'];
            $bobot = (float) $item['bobot'];
            $terbobot = ($nilai * $bobot) / 100;
            return [
                'nama' => $item['nama'],
                'bobot' => $bobot,
                'nilai' => $nilai,
                'nilai_terbobot' => round($terbobot, 2),
            ];
        })->values()->all();

        $totalNilai = round(collect($rincian)->sum('nilai_terbobot'), 2);

        PenilaianTahapDua::query()->updateOrCreate(
            [
                'edisi_lomba_id' => $edisi->id,
                'karya_peserta_id' => $karya->id,
                'juri_id' => $targetJuriId,
            ],
            [
                'rincian_nilai' => $rincian,
                'total_nilai' => $totalNilai,
                'catatan' => $validated['catatan'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Nilai berhasil disimpan.')->setStatusCode(303);
    }


    public function rekap(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehAkses($request, (int) $edisi->id), 403);

        $karyaNominasi = KaryaPeserta::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('status', 'submitted')
            ->where('lolos_nominasi', true)
            ->when(!$this->isPrivileged($request), function ($q) use ($edisi, $request) {
                $assignedKategoriIds = PenugasanJuriKategori::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('juri_id', (int) $request->user()->id)
                    ->pluck('kategori_lomba_id')
                    ->all();

                if (empty($assignedKategoriIds)) {
                    $q->whereRaw('1 = 0');
                    return;
                }

                $q->whereIn('kategori_lomba_id', $assignedKategoriIds);
            })
            ->get(['id', 'nama_karya', 'nama_kategori']);

        $penilaian = PenilaianTahapDua::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->get(['karya_peserta_id', 'total_nilai']);

        $rekap = $karyaNominasi->map(function (KaryaPeserta $karya) use ($penilaian) {
            $rows = $penilaian->where('karya_peserta_id', $karya->id);
            $count = $rows->count();
            $avg = $count > 0 ? round((float) $rows->avg('total_nilai'), 2) : null;
            $sum = $count > 0 ? round((float) $rows->sum('total_nilai'), 2) : null;
            return [
                'id' => $karya->id,
                'nama_karya' => $karya->nama_karya,
                'nama_kategori' => $karya->nama_kategori,
                'jumlah_penilai' => $count,
                'total_nilai' => $sum,
                'rata_rata' => $avg,
            ];
        })->sortByDesc('rata_rata')->values();

        return Inertia::render('Penjurian/Rekap', [
            'rekap' => $rekap,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
        ]);
    }
}
