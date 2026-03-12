<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\LampiranKaryaPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    private function transformSubmission(KaryaPeserta $item, string $prefix): array
    {
        return [
            'id' => $item->id,
            'nama_karya' => $item->nama_karya,
            'nama_kategori' => $item->nama_kategori,
            'status' => $item->status,
            'is_lolos_nominasi' => (bool) $item->lolos_nominasi,
            'submitted_at' => $item->submitted_at?->format('d M Y, H:i'),
            'updated_at' => $item->updated_at?->format('d M Y, H:i'),
            'wa_ketua' => $item->wa_ketua,
            'dosen_pembimbing' => $item->dosen_pembimbing ?? [],
            'proposal_path' => $item->proposal_path,
            'link_tambahan' => $item->link_tambahan ?? [],
            'nilai_tahap_1' => $item->nilai_tahap_1,
            'catatan_tahap_1' => $item->catatan_tahap_1,
            'anggota_tim' => $item->anggota_tim ?? [],
            'jumlah_lampiran' => $item->lampiran->count(),
            'peserta' => [
                'id' => $item->peserta?->id,
                'name' => $item->peserta?->name,
                'email' => $item->peserta?->email,
                'avatar' => $item->peserta?->avatar,
            ],
            'lampiran' => $item->lampiran->map(function (LampiranKaryaPeserta $lampiran) use ($prefix) {
                return [
                    'id' => $lampiran->id,
                    'nama' => $lampiran->nama_asli,
                    'deskripsi' => $lampiran->deskripsi,
                    'url' => "/{$prefix}/submission/lampiran/{$lampiran->id}/preview",
                ];
            })->values(),
        ];
    }

    private function resolveEdisiKonteks(Request $request): Edition
    {
        $user = $request->user();
        $role = $request->segment(1);

        $query = Edition::query()->orderByDesc('tahun');
        if ($role === 'admin') {
            // Admin boleh memilih semua edisi.
        } else {
            $query->whereHas('roles', function ($q) use ($user, $role) {
                $q->where('roles.name', $role)
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

    private function bolehKelola(Request $request, int $edisiId): bool
    {
        $user = $request->user();
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    private function renderIndex(Request $request, bool $onlyNominasi = false)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $bolehKelola = $this->bolehKelola($request, (int) $edisi->id);
        $prefix = (string) $request->segment(1);

        $query = KaryaPeserta::query()
            ->with(['peserta:id,name,email,avatar', 'lampiran:id,karya_peserta_id'])
            ->where('edisi_lomba_id', $edisi->id)
            ->orderByDesc('submitted_at');

        if ($onlyNominasi) {
            $query->where('lolos_nominasi', true);
        }

        $data = $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (KaryaPeserta $item) => $this->transformSubmission($item, $prefix))
            ->values();

        return Inertia::render('Submission/Index', [
            'submissions' => $data,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'bolehKelola' => $bolehKelola,
            'mode' => $onlyNominasi ? 'nominasi' : 'karya',
        ]);
    }

    public function index(Request $request)
    {
        return redirect()->to('/' . $request->segment(1) . '/submission/karya');
    }

    public function karya(Request $request)
    {
        return $this->renderIndex($request, false);
    }

    public function nominasi(Request $request)
    {
        return $this->renderIndex($request, true);
    }

    public function show(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $prefix = (string) $request->segment(1);
        $karya->load(['peserta:id,name,email,avatar', 'lampiran:id,karya_peserta_id,nama_asli,deskripsi']);

        return Inertia::render('Submission/Show', [
            'submission' => $this->transformSubmission($karya, $prefix),
            'gemasiAktifLabel' => optional($karya->edisi)->nama . ' (' . optional($karya->edisi)->tahun . ')',
            'bolehKelola' => $this->bolehKelola($request, (int) $karya->edisi_lomba_id),
        ]);
    }

    public function updateNilaiTahapSatu(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $validated = $request->validate([
            'nilai_tahap_1' => 'nullable|integer|min:0|max:100',
            'catatan_tahap_1' => 'nullable|string',
        ]);

        $karya->nilai_tahap_1 = $validated['nilai_tahap_1'] ?? null;
        $karya->catatan_tahap_1 = $validated['catatan_tahap_1'] ?? null;
        $karya->save();

        return redirect()->back()->with('success', 'Nilai tahap 1 berhasil disimpan.');
    }

    public function updateAnggotaTim(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $validated = $request->validate([
            'anggota_tim' => 'required|array|min:1|max:6',
            'anggota_tim.*.nim' => 'required|string|max:50',
            'anggota_tim.*.nama' => 'required|string|max:255',
            'anggota_tim.*.email' => 'required|string|max:255',
            'anggota_tim.*.peran' => 'required|string|max:50',
        ]);

        $karya->anggota_tim = $validated['anggota_tim'];
        $karya->save();

        return redirect()->back()->with('success', 'Anggota tim berhasil diperbarui.');
    }

    public function destroy(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $karya->load('lampiran');
        DB::transaction(function () use ($karya) {
            foreach ($karya->lampiran as $lampiran) {
                Storage::disk('public')->delete($lampiran->path_file);
                $lampiran->delete();
            }

            $karya->delete();
        });

        return redirect()->back()->with('success', 'Submission berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $items = KaryaPeserta::query()
            ->with('lampiran')
            ->where('edisi_lomba_id', $edisi->id)
            ->whereIn('id', $validated['ids'])
            ->get();

        DB::transaction(function () use ($items) {
            foreach ($items as $karya) {
                foreach ($karya->lampiran as $lampiran) {
                    Storage::disk('public')->delete($lampiran->path_file);
                    $lampiran->delete();
                }

                $karya->delete();
            }
        });

        return redirect()->back()->with('success', 'Submission terpilih berhasil dihapus.');
    }

    public function lolosNominasi(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted', 422, 'Hanya submission lengkap yang bisa diloloskan.');
        abort_unless($karya->nilai_tahap_1 !== null, 422, 'Nilai tahap 1 belum diisi.');

        $karya->lolos_nominasi = true;
        $karya->save();

        return redirect()->back()->with('success', 'Karya ditandai lolos nominasi.');
    }

    public function batalkanNominasi(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $karya->lolos_nominasi = false;
        $karya->save();

        return redirect()->back()->with('success', 'Status lolos nominasi dibatalkan.');
    }

    public function previewLampiran(Request $request, LampiranKaryaPeserta $lampiran)
    {
        $karya = $lampiran->karya;
        abort_unless($karya, 404);
        $edisi = $this->resolveEdisiKonteks($request);
        abort_unless($this->bolehKelola($request, (int) $edisi->id), 403);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless(Storage::disk('public')->exists($lampiran->path_file), 404);

        return Storage::disk('public')->response(
            $lampiran->path_file,
            $lampiran->nama_asli,
            ['Content-Type' => $lampiran->mime_type ?: 'application/octet-stream']
        );
    }
}
