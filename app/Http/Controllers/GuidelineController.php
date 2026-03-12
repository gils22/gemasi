<?php

namespace App\Http\Controllers;

use App\Models\BobotPenilaianKategori;
use App\Models\Edition;
use App\Models\KategoriLomba;
use App\Models\PanduanLomba;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GuidelineController extends Controller
{
    private function decodeKetentuan(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map(
                fn ($item) => is_string($item) ? trim($item) : '',
                $decoded
            )));
        }

        return array_values(array_filter(array_map(
            fn ($line) => trim($line),
            preg_split('/\r\n|\r|\n/', $raw) ?: []
        )));
    }

    private function decodeKriteria(?string $rawCatatan): array
    {
        $decoded = $rawCatatan ? json_decode($rawCatatan, true) : null;
        if (is_array($decoded) && isset($decoded['kriteria']) && is_array($decoded['kriteria'])) {
            $result = array_values(array_filter(array_map(function ($item) {
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

            // Legacy fallback lama: 1 baris "Penilaian Utama" bernilai 100.
            // Untuk edisi baru, ini diperlakukan sebagai belum diisi.
            if (
                count($result) === 1 &&
                ($result[0]['nama'] ?? '') === 'Penilaian Utama' &&
                (float) ($result[0]['poin'] ?? 0) === 100.0
            ) {
                return [];
            }

            return $result;
        }

        return [];
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

    public function ketentuan(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $isAdmin = $request->user()?->hasRole('admin') === true;
        $basePath = '/admin';

        $panduan = PanduanLomba::query()->firstOrCreate(
            ['edisi_lomba_id' => $edisi->id],
            ['ketentuan_umum' => null, 'tautan_pdf' => null]
        );

        return Inertia::render('Guideline/Ketentuan', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'isEditable' => $edisi->status === 'aktif',
            'isAdmin' => $isAdmin,
            'basePath' => $basePath,
            'panduan' => [
                'ketentuan' => $this->decodeKetentuan($panduan->ketentuan_umum),
                'panduan_tahap_2' => $panduan->tautan_pdf,
            ],
        ]);
    }

    public function updateKetentuan(Request $request)
    {
        $validated = $request->validate([
            'edisi_id' => 'required|integer|exists:edisi_lomba,id',
            'ketentuan' => 'nullable|array',
            'ketentuan.*' => 'nullable|string|max:1000',
            'panduan_tahap_2' => 'nullable|string|max:2048',
        ]);

        $edisi = $this->resolveEdisiKonteks($request);
        if ((int) $validated['edisi_id'] !== (int) $edisi->id) {
            $validated['edisi_id'] = $edisi->id;
        }
        abort_if($edisi->status !== 'aktif', 403, 'Hanya edisi aktif yang dapat diubah.');

        $ketentuanList = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $validated['ketentuan'] ?? []
        )));

        PanduanLomba::query()->updateOrCreate(
            ['edisi_lomba_id' => $edisi->id],
            [
                'ketentuan_umum' => !empty($ketentuanList)
                    ? json_encode($ketentuanList, JSON_UNESCAPED_UNICODE)
                    : null,
                'tautan_pdf' => ($validated['panduan_tahap_2'] ?? null) ?: null,
            ]
        );

        return redirect()->back()->setStatusCode(303);
    }

    public function bobot(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $isAdmin = $request->user()?->hasRole('admin') === true;
        $basePath = '/admin';

        $kategori = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $bobotMap = BobotPenilaianKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->get()
            ->keyBy('kategori_lomba_id');

        $bobotKategori = $kategori->map(function (KategoriLomba $item) use ($bobotMap) {
            $bobot = $bobotMap->get($item->id);
            return [
                'kategori_lomba_id' => $item->id,
                'nama_kategori' => $item->nama,
                'kriteria' => $this->decodeKriteria($bobot?->catatan),
            ];
        })->values();

        return Inertia::render('Guideline/Bobot', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'isEditable' => $edisi->status === 'aktif',
            'isAdmin' => $isAdmin,
            'basePath' => $basePath,
            'bobotKategori' => $bobotKategori,
        ]);
    }

    public function updateBobot(Request $request)
    {
        $validated = $request->validate([
            'edisi_id' => 'required|integer|exists:edisi_lomba,id',
            'bobot' => 'nullable|array',
            'bobot.*.kategori_lomba_id' => 'required|integer|exists:kategori_lomba,id',
            'bobot.*.kriteria' => 'required|array|min:1',
            'bobot.*.kriteria.*.nama' => 'required|string|max:255',
            'bobot.*.kriteria.*.poin' => 'required|numeric|min:0|max:100',
        ]);

        $edisi = $this->resolveEdisiKonteks($request);
        if ((int) $validated['edisi_id'] !== (int) $edisi->id) {
            $validated['edisi_id'] = $edisi->id;
        }
        abort_if($edisi->status !== 'aktif', 403, 'Hanya edisi aktif yang dapat diubah.');

        foreach (($validated['bobot'] ?? []) as $item) {
            $total = collect($item['kriteria'] ?? [])->sum(
                fn (array $kriteria) => (float) ($kriteria['poin'] ?? 0)
            );

            if (round($total, 2) !== 100.00) {
                throw ValidationException::withMessages([
                    'bobot' => 'Total poin tiap kategori harus 100.',
                ]);
            }
        }

        $payload = collect($validated['bobot'] ?? [])->map(function (array $item) use ($edisi) {
            $kriteriaBersih = array_values(array_map(function (array $kriteria) {
                return [
                    'nama' => trim((string) $kriteria['nama']),
                    'poin' => (float) $kriteria['poin'],
                ];
            }, $item['kriteria']));

            return [
                'edisi_lomba_id' => $edisi->id,
                'kategori_lomba_id' => (int) $item['kategori_lomba_id'],
                'persentase' => collect($kriteriaBersih)->sum('poin'),
                'catatan' => json_encode(['kriteria' => $kriteriaBersih], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        })->all();

        $kategoriIdsEdisi = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->pluck('id')
            ->all();

        $payload = array_values(array_filter(
            $payload,
            fn (array $item) => in_array($item['kategori_lomba_id'], $kategoriIdsEdisi, true)
        ));

        if (!empty($payload)) {
            BobotPenilaianKategori::query()->upsert(
                $payload,
                ['edisi_lomba_id', 'kategori_lomba_id'],
                ['persentase', 'catatan', 'updated_at']
            );
        }

        BobotPenilaianKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->whereNotIn('kategori_lomba_id', array_column($payload, 'kategori_lomba_id'))
            ->delete();

        return redirect()->back()->setStatusCode(303);
    }

    public function template(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $isAdmin = $request->user()?->hasRole('admin') === true;
        $basePath = '/admin';

        $panduan = PanduanLomba::query()->firstOrCreate(
            ['edisi_lomba_id' => $edisi->id],
            ['ketentuan_umum' => null, 'tautan_pdf' => null]
        );

        return Inertia::render('Guideline/Template', [
            'edisiAktif' => [
                'id' => $edisi->id,
                'nama' => $edisi->nama,
                'tahun' => $edisi->tahun,
                'status' => $edisi->status,
                'aktif' => (bool) $edisi->aktif,
            ],
            'isEditable' => $edisi->status === 'aktif',
            'isAdmin' => $isAdmin,
            'basePath' => $basePath,
            'templateProposal' => [
                'nama' => $panduan->template_proposal_nama_tampil,
                'url' => $panduan->template_proposal_path ?: null,
            ],
        ]);
    }

    public function updateTemplate(Request $request)
    {
        $validated = $request->validate([
            'edisi_id' => 'required|integer|exists:edisi_lomba,id',
            'template_proposal_name' => 'nullable|string|max:255',
            'template_proposal_url' => 'nullable|string|max:2048',
        ]);

        $edisi = $this->resolveEdisiKonteks($request);
        if ((int) $validated['edisi_id'] !== (int) $edisi->id) {
            $validated['edisi_id'] = $edisi->id;
        }
        abort_if($edisi->status !== 'aktif', 403, 'Hanya edisi aktif yang dapat diubah.');

        PanduanLomba::query()->updateOrCreate(
            ['edisi_lomba_id' => $edisi->id],
            [
                'template_proposal_nama_tampil' => ($validated['template_proposal_name'] ?? null) ?: null,
                'template_proposal_path' => ($validated['template_proposal_url'] ?? null) ?: null,
            ]
        );

        return redirect()->back()->setStatusCode(303);
    }
}
