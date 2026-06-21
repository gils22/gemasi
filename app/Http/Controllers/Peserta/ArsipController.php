<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\PemenangKarya;
use App\Models\LampiranKaryaPeserta;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ArsipController extends Controller
{
    private function normalizedEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function isCurrentUserMemberOfKarya(KaryaPeserta $karya, ?string $email): bool
    {
        $email = $this->normalizedEmail($email);
        if ($email === '') {
            return false;
        }

        return collect($karya->anggota_tim ?? [])->contains(function ($anggota) use ($email) {
            if (!is_array($anggota)) {
                return false;
            }

            return $this->normalizedEmail($anggota['email'] ?? null) === $email;
        });
    }

    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;
        $userEmail = $request->user()->email;
        $edisiDipilih = $request->integer('edisi');

        $edisiOpsi = Edition::query()
            ->whereIn('id', function ($query) use ($userId, $userEmail) {
                $query->from('karya_peserta')
                    ->select('edisi_lomba_id')
                    ->where(function ($subQuery) use ($userId, $userEmail) {
                        $subQuery->where('user_id', $userId)
                            ->orWhereRaw(
                                "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                                [$userEmail],
                            );
                    })
                    ->groupBy('edisi_lomba_id');
            })
            ->orderByDesc('tahun')
            ->get(['id', 'nama', 'tahun'])
            ->map(fn (Edition $edition) => [
                'id' => $edition->id,
                'label' => $edition->nama . ' (' . $edition->tahun . ')',
            ])
            ->values();

        $arsipQuery = KaryaPeserta::query()
            ->with('edisi')
            ->where(function ($query) use ($userId, $userEmail) {
                $query->where('user_id', $userId)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$userEmail],
                    );
            })
            ->whereHas('edisi', fn ($query) => $query->where('status', 'arsip'))
            ->when($edisiDipilih, fn ($query) => $query->where('edisi_lomba_id', $edisiDipilih))
            ->orderByDesc('updated_at');

        $arsipKarya = $arsipQuery
            ->get()
            ->map(function (KaryaPeserta $item) {
                $anggota = collect($item->anggota_tim ?? []);
                $ketua = $anggota->firstWhere('peran', 'ketua');
                $pemenang = PemenangKarya::query()
                    ->where('karya_peserta_id', $item->id)
                    ->orderBy('peringkat')
                    ->first();
                $lampiran = $item->lampiran()
                    ->orderBy('urutan')
                    ->get()
                    ->map(fn ($lampiran) => [
                        'id' => $lampiran->id,
                        'nama_asli' => $lampiran->nama_asli,
                        'deskripsi' => $lampiran->deskripsi,
                        'url' => route('peserta.daftar-karya.lampiran.preview', ['lampiran' => $lampiran->id]),
                    ])
                    ->values();

                return [
                    'id' => $item->id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'edisi_id' => $item->edisi_lomba_id,
                    'wa_ketua' => $item->wa_ketua,
                    'dosen_pembimbing' => $item->dosen_pembimbing,
                    'proposal_link' => $item->proposal_path,
                    'link_tambahan' => $item->link_tambahan ?? [],
                    'lampiran' => $lampiran,
                    'jumlah_anggota_tim' => $anggota->count(),
                    'nama_ketua' => $ketua['nama'] ?? null,
                    'status_tampilan' => $item->status === 'submitted'
                        ? 'Lengkap'
                        : 'Tahap 1 tersimpan',
                    'updated_at' => optional($item->updated_at)->format('d M Y, H:i'),
                    'edisi_label' => optional($item->edisi)->nama ?? '-',
                    'is_juara' => (bool) $pemenang,
                    'is_nominasi' => (bool) $item->lolos_nominasi && ! $pemenang,
                    'peringkat' => $pemenang?->peringkat,
                    'pameran_logo_name' => $item->pameran_logo_nama_asli,
                    'pameran_logo_url' => $item->pameran_logo_path
                        ? route('peserta.pameran.logo.preview', ['karya' => $item->id])
                        : null,
                    'pameran_link_video' => $item->pameran_link_video,
                    'pameran_ringkasan' => $item->pameran_ringkasan,
                    'pameran_submitted_at' => $item->pameran_submitted_at?->format('d M Y, H:i'),
                    'anggota_tim' => $item->anggota_tim,
                ];
            })
            ->values();

        return Inertia::render('Peserta/Arsip/Index', [
            'arsipKarya' => $arsipKarya,
            'edisiOpsi' => $edisiOpsi,
            'edisiTerpilih' => $edisiDipilih ?: null,
        ]);
    }

    public function show(Request $request, KaryaPeserta $karya)
    {
        $userId = (int) $request->user()->id;
        $userEmail = $request->user()->email;

        abort_unless(
            $karya->user_id === $userId || $this->isCurrentUserMemberOfKarya($karya, $userEmail),
            403
        );

        $karya->load(['edisi', 'lampiran']);
        abort_unless($karya->edisi && $karya->edisi->status === 'arsip', 404);

        $anggota = collect($karya->anggota_tim ?? []);
        $pemenang = PemenangKarya::query()
            ->where('karya_peserta_id', $karya->id)
            ->orderBy('peringkat')
            ->first();

        return Inertia::render('Peserta/Arsip/Detail', [
            'karya' => [
                'id' => $karya->id,
                'nama_karya' => $karya->nama_karya,
                'nama_kategori' => $karya->nama_kategori,
                'edisi_label' => optional($karya->edisi)->nama . ' (' . optional($karya->edisi)->tahun . ')',
                'status_tampilan' => $karya->status === 'submitted' ? 'Lengkap' : 'Tahap 1 tersimpan',
                'is_juara' => (bool) $pemenang,
                'is_nominasi' => (bool) $karya->lolos_nominasi && ! $pemenang,
                'peringkat' => $pemenang?->peringkat,
                'wa_ketua' => $karya->wa_ketua,
                'dosen_pembimbing' => $karya->dosen_pembimbing,
                'proposal_link' => $karya->proposal_path,
                'anggota_tim' => $anggota->values(),
                'lampiran' => $karya->lampiran->map(fn (LampiranKaryaPeserta $item) => [
                    'id' => $item->id,
                    'nama_asli' => $item->nama_asli,
                    'deskripsi' => $item->deskripsi,
                    'url' => route('peserta.daftar-karya.lampiran.preview', ['lampiran' => $item->id]),
                ])->values(),
                'pameran_logo_name' => $karya->pameran_logo_nama_asli,
                'pameran_logo_url' => $karya->pameran_logo_path
                    ? route('peserta.pameran.logo.preview', ['karya' => $karya->id])
                    : null,
                'pameran_link_video' => $karya->pameran_link_video,
                'pameran_ringkasan' => $karya->pameran_ringkasan,
                'pameran_submitted_at' => $karya->pameran_submitted_at?->format('d M Y, H:i'),
            ],
        ]);
    }
}
