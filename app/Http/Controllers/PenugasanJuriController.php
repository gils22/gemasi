<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\KategoriLomba;
use App\Models\PenugasanJuriKategori;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PenugasanJuriController extends Controller
{
    private function resolveEdisiKonteks(): Edition
    {
        $selectedId = (int) session('edisi_aktif_id');
        if ($selectedId > 0) {
            $bySession = Edition::query()->find($selectedId);
            if ($bySession) {
                return $bySession;
            }
        }

        $tahunSekarang = (int) now()->format('Y');

        $edisi = Edition::query()->where('status', 'aktif')->first()
            ?? Edition::query()->where('aktif', true)->first()
            ?? Edition::query()->where('tahun', $tahunSekarang)->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        abort_if(!$edisi, 500, 'Edisi lomba belum tersedia.');
        session(['edisi_aktif_id' => $edisi->id]);
        return $edisi;
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        $edisi = $this->resolveEdisiKonteks();

        $kategori = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $juriOptions = User::query()
            ->whereHas('editionRoles', function ($q) use ($edisi) {
                $q->where('roles.name', 'juri')
                    ->wherePivot('edisi_lomba_id', $edisi->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $penugasan = PenugasanJuriKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->orderBy('id')
            ->get(['kategori_lomba_id', 'juri_id'])
            ->groupBy('kategori_lomba_id')
            ->map(fn ($rows) => $rows->pluck('juri_id')->values())
            ->all();

        return Inertia::render('Penjurian/Penugasan', [
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'kategori' => $kategori,
            'juriOptions' => $juriOptions,
            'penugasan' => $penugasan,
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        $edisi = $this->resolveEdisiKonteks();

        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.kategori_lomba_id' => 'required|integer|exists:kategori_lomba,id',
            'assignments.*.juri_ids' => 'required|array|size:2',
            'assignments.*.juri_ids.*' => 'required|integer|distinct|exists:users,id',
            'assignments.*.tahap' => 'nullable|string|in:tahap_1,tahap_2,tahap_1_2',
        ]);

        $kategoriAktifIds = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->pluck('id')
            ->all();

        $assignmentKategoriIds = collect($validated['assignments'])->pluck('kategori_lomba_id')->map(fn ($id) => (int) $id)->all();
        sort($kategoriAktifIds);
        $sortedInput = $assignmentKategoriIds;
        sort($sortedInput);
        if ($kategoriAktifIds !== $sortedInput) {
            throw ValidationException::withMessages([
                'assignments' => 'Semua kategori aktif wajib memiliki tepat 2 juri.',
            ]);
        }

        $juriValidIds = User::query()
            ->whereHas('editionRoles', function ($q) use ($edisi) {
                $q->where('roles.name', 'juri')
                    ->wherePivot('edisi_lomba_id', $edisi->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $payload = [];
        foreach ($validated['assignments'] as $row) {
            $kategoriId = (int) $row['kategori_lomba_id'];
            $tahap = $row['tahap'] ?? 'tahap_2';
            $juriIds = collect($row['juri_ids'])->map(fn ($id) => (int) $id)->values();
            if ($juriIds->count() !== 2 || $juriIds->unique()->count() !== 2) {
                throw ValidationException::withMessages([
                    'assignments' => 'Setiap kategori harus memiliki 2 juri berbeda.',
                ]);
            }

            foreach ($juriIds as $juriId) {
                if (!in_array($juriId, $juriValidIds, true)) {
                    throw ValidationException::withMessages([
                        'assignments' => 'Terdapat user yang bukan juri pada edisi aktif.',
                    ]);
                }
                $payload[] = [
                    'edisi_lomba_id' => $edisi->id,
                    'kategori_lomba_id' => $kategoriId,
                    'juri_id' => $juriId,
                    'tahap' => $tahap,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($edisi, $payload) {
            PenugasanJuriKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->delete();

            PenugasanJuriKategori::query()->insert($payload);
        });

        return redirect()->back()->with('success', 'Penugasan juri berhasil disimpan.')->setStatusCode(303);
    }
}
