<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\LampiranKaryaPeserta;
use App\Models\PenugasanJuriKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class JuriSubmissionController extends Controller
{
    private function resolveEdisiKonteks(Request $request): Edition
    {
        $user = $request->user();

        $query = Edition::query()
            ->whereHas('roles', function ($q) use ($user) {
                $q->where('roles.name', 'juri')
                    ->where('edisi_lomba_user_role.user_id', $user->id);
            })
            ->orderByDesc('tahun');

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

    private function assignedKategoriIds(Request $request, int $edisiId): array
    {
        return PenugasanJuriKategori::query()
            ->where('edisi_lomba_id', $edisiId)
            ->where('juri_id', (int) $request->user()->id)
            ->whereIn('tahap', ['tahap_1', 'tahap_1_2'])
            ->pluck('kategori_lomba_id')
            ->unique()
            ->values()
            ->all();
    }

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

    public function index(Request $request)
    {
        return redirect()->to('/juri/submission/karya');
    }

    public function karya(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);

        $data = KaryaPeserta::query()
            ->with(['peserta:id,name,email,avatar', 'lampiran:id,karya_peserta_id'])
            ->where('edisi_lomba_id', $edisi->id)
            ->when(
                empty($kategoriIds),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('kategori_lomba_id', $kategoriIds)
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (KaryaPeserta $item) => $this->transformSubmission($item, 'juri'))
            ->values();

        return Inertia::render('Submission/Index', [
            'submissions' => $data,
            'gemasiAktifLabel' => $edisi->nama . ' (' . $edisi->tahun . ')',
            'bolehKelola' => false,
            'bolehLoloskanNominasi' => true,
            'bolehNilaiTahap1' => true,
            'mode' => 'karya',
            'kategoriOptions' => [],
        ]);
    }

    public function show(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);
        abort_unless(in_array((int) $karya->kategori_lomba_id, $kategoriIds, true), 403);

        $karya->load(['peserta:id,name,email,avatar', 'lampiran:id,karya_peserta_id,nama_asli,deskripsi']);

        return Inertia::render('Submission/Show', [
            'submission' => $this->transformSubmission($karya, 'juri'),
            'gemasiAktifLabel' => optional($karya->edisi)->nama . ' (' . optional($karya->edisi)->tahun . ')',
            'bolehKelola' => false,
            'bolehLoloskanNominasi' => true,
            'bolehNilaiTahap1' => true,
        ]);
    }

    public function updateNilaiTahapSatu(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);
        abort_unless(in_array((int) $karya->kategori_lomba_id, $kategoriIds, true), 403);

        $validated = $request->validate([
            'nilai_tahap_1' => 'nullable|integer|min:0|max:100',
            'catatan_tahap_1' => 'nullable|string',
        ]);

        $karya->nilai_tahap_1 = $validated['nilai_tahap_1'] ?? null;
        $karya->catatan_tahap_1 = $validated['catatan_tahap_1'] ?? null;
        $karya->save();

        return redirect()->back()->with('success', 'Nilai tahap 1 berhasil disimpan.')->setStatusCode(303);
    }

    public function previewLampiran(Request $request, LampiranKaryaPeserta $lampiran)
    {
        $karya = $lampiran->karya;
        abort_unless($karya, 404);

        $edisi = $this->resolveEdisiKonteks($request);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);
        abort_unless(in_array((int) $karya->kategori_lomba_id, $kategoriIds, true), 403);

        abort_unless(Storage::disk('public')->exists($lampiran->path_file), 404);

        return Storage::disk('public')->response(
            $lampiran->path_file,
            $lampiran->nama_asli,
            ['Content-Type' => $lampiran->mime_type ?: 'application/octet-stream']
        );
    }

    public function lolosNominasi(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);
        abort_unless($karya->status === 'submitted', 422, 'Hanya submission lengkap yang bisa diloloskan.');

        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);
        abort_unless(in_array((int) $karya->kategori_lomba_id, $kategoriIds, true), 403);
        abort_unless($karya->nilai_tahap_1 !== null, 422, 'Nilai tahap 1 belum diisi.');

        $karya->lolos_nominasi = true;
        $karya->save();

        return redirect()->back()->with('success', 'Karya ditandai lolos nominasi.')->setStatusCode(303);
    }

    public function batalkanNominasi(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        abort_if((int) $karya->edisi_lomba_id !== (int) $edisi->id, 404);

        $kategoriIds = $this->assignedKategoriIds($request, (int) $edisi->id);
        abort_unless(in_array((int) $karya->kategori_lomba_id, $kategoriIds, true), 403);

        $karya->lolos_nominasi = false;
        $karya->save();

        return redirect()->back()->with('success', 'Status lolos nominasi dibatalkan.')->setStatusCode(303);
    }
}
