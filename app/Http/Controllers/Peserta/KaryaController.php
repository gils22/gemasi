<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\KaryaPeserta;
use App\Models\KategoriLomba;
use App\Models\LampiranKaryaPeserta;
use App\Models\PanduanLomba;
use App\Models\TimelineLomba;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KaryaController extends Controller
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

    private function canAccessKarya(Request $request, KaryaPeserta $karya): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ((int) $karya->user_id === (int) $user->id) {
            return true;
        }

        return $this->isCurrentUserMemberOfKarya($karya, $user->email);
    }

    private function normalizeAnggotaTim(array $anggotaTim): array
    {
        return collect($anggotaTim)
            ->map(function ($anggota, $index) {
                $nama = trim((string) ($anggota['nama'] ?? ''));
                $email = strtolower(trim((string) ($anggota['email'] ?? '')));
                $peran = trim((string) ($anggota['peran'] ?? ''));
                $nim = trim((string) ($anggota['nim'] ?? ''));

                return [
                    'nim' => $nim !== '' ? $nim : null,
                    'nama' => $nama,
                    'email' => $email !== '' ? $email : null,
                    'peran' => $peran !== '' ? $peran : ($index === 0 ? 'ketua' : 'anggota'),
                ];
            })
            ->values()
            ->all();
    }

    private function validateAnggotaTim(array $anggotaTim): void
    {
        $anggota = collect($anggotaTim);

        abort_unless($anggota->where('peran', 'ketua')->count() === 1, 422, 'Harus ada tepat satu ketua tim.');

        $emails = $anggota->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)));

        abort_if($emails->duplicates()->isNotEmpty(), 422, 'Email anggota tim tidak boleh duplikat.');
    }

    private function resolvePeranAkses(KaryaPeserta $karya, ?string $email): string
    {
        $email = $this->normalizedEmail($email);
        $anggota = collect($karya->anggota_tim ?? []);
        $ketua = $anggota->firstWhere('peran', 'ketua');

        if ((int) $karya->user_id === (int) request()->user()?->id) {
            return 'ketua';
        }

        if ($email !== '' && $this->isCurrentUserMemberOfKarya($karya, $email)) {
            return $this->normalizedEmail($ketua['email'] ?? null) === $email ? 'ketua' : 'anggota';
        }

        return 'anggota';
    }

    private function resolveKaryaByUser(Request $request, int $karyaId): ?KaryaPeserta
    {
        if ($karyaId <= 0) {
            return null;
        }

        return KaryaPeserta::query()
            ->with(['lampiran', 'edisi'])
            ->where('id', $karyaId)
            ->where(function ($query) use ($request) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$this->normalizedEmail($request->user()->email)],
                    );
            })
            ->first();
    }

    private function resolveKaryaDraft(Request $request, Edition $edisi, int $karyaId): ?KaryaPeserta
    {
        if ($karyaId <= 0) {
            return null;
        }

        return KaryaPeserta::query()
            ->where('id', $karyaId)
            ->where('edisi_lomba_id', $edisi->id)
            ->where(function ($query) use ($request) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$this->normalizedEmail($request->user()->email)],
                    );
            })
            ->first();
    }

    private function persistDraft(Request $request, Edition $edisi, array $validated): KaryaPeserta
    {
        $user = $request->user();
        $karyaId = (int) ($validated['id'] ?? 0);
        $karya = $this->resolveKaryaDraft($request, $edisi, $karyaId);

        if (!$karya) {
            $karya = new KaryaPeserta();
            $karya->edisi_lomba_id = $edisi->id;
            $karya->user_id = (int) $user->id;
            $karya->status = 'draft';
        }

        if (array_key_exists('kategori', $validated)) {
            $kategori = KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('nama', $validated['kategori'])
                ->first();

            $karya->kategori_lomba_id = $kategori?->id;
            $karya->nama_kategori = $validated['kategori'];
        }

        if (array_key_exists('namaKarya', $validated)) {
            $karya->nama_karya = $validated['namaKarya'];
        }

        if (array_key_exists('waKetua', $validated)) {
            $karya->wa_ketua = $validated['waKetua'];
        }

        if (array_key_exists('dosenPembimbing', $validated)) {
            $karya->dosen_pembimbing = $validated['dosenPembimbing'] ?? null;
        }

        if (array_key_exists('anggotaTim', $validated)) {
            $karya->anggota_tim = $this->normalizeAnggotaTim($validated['anggotaTim']);
        }

        if (array_key_exists('proposalLink', $validated)) {
            $karya->proposal_path = trim((string) $validated['proposalLink']) !== ''
                ? trim((string) $validated['proposalLink'])
                : null;
        }

        if (array_key_exists('linkTambahan', $validated)) {
            $karya->link_tambahan = $validated['linkTambahan'] ?? [];
        }

        $karya->save();

        return $karya;
    }

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

    private function pendaftaranMasihDibuka(Edition $edisi): bool
    {
        $aktif = ($edisi->status === 'aktif') || (bool) $edisi->aktif;
        if (!$aktif) {
            return false;
        }

        $timelinePendaftaran = TimelineLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('fase_kunci', 'pendaftaran')
            ->where('aktif', true)
            ->orderByRaw('CASE WHEN mulai_pada IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mulai_pada')
            ->orderBy('id')
            ->get(['mulai_pada', 'selesai_pada', 'is_tba']);

        // Fallback aman: jika fase pendaftaran belum diset, sementara tetap dibuka.
        if ($timelinePendaftaran->isEmpty()) {
            return true;
        }

        $now = now();

        foreach ($timelinePendaftaran as $item) {
            if ((bool) $item->is_tba) {
                return true;
            }

            $mulai = $item->mulai_pada ? Carbon::parse($item->mulai_pada) : null;
            $selesai = $item->selesai_pada ? Carbon::parse($item->selesai_pada) : null;

            if (!$mulai && !$selesai) {
                return true;
            }

            if ($mulai && !$selesai && $now->gte($mulai)) {
                return true;
            }

            if (!$mulai && $selesai && $now->lte($selesai)) {
                return true;
            }

            if ($mulai && $selesai && $now->betweenIncluded($mulai, $selesai)) {
                return true;
            }
        }

        return false;
    }

    public function index(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        $pendaftaranDibuka = $this->pendaftaranMasihDibuka($edisi);
        $isBuatBaru = $request->boolean('baru');
        $karyaId = (int) $request->query('karya');
        $selectedKarya = KaryaPeserta::query()
            ->with(['lampiran', 'edisi'])
            ->where('id', $karyaId)
            ->where('edisi_lomba_id', $edisi->id)
            ->where(function ($query) use ($request) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$this->normalizedEmail($request->user()->email)],
                    );
            })
            ->first();
        $isArsipReadOnly = (bool) (
            $selectedKarya &&
            optional($selectedKarya->edisi)->status === 'arsip'
        );
        $isMemberOnly = (bool) (
            $selectedKarya &&
            (int) $selectedKarya->user_id !== (int) $request->user()->id
        );

        if (!$pendaftaranDibuka && !$isArsipReadOnly) {
            return redirect()
                ->route('peserta.daftar-karya')
                ->with('error', 'Pendaftaran sudah ditutup. Tambah atau edit karya tidak tersedia.');
        }

        $edisiForm = $isArsipReadOnly ? $selectedKarya->edisi : $edisi;
        $kategoriAktif = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisiForm->id)
            ->where('aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $karya = null;
        if (!$isBuatBaru) {
            if ($isArsipReadOnly || $isMemberOnly) {
                $karya = $selectedKarya;
            } else {
                $baseQuery = KaryaPeserta::query()
                    ->with('lampiran')
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('user_id', (int) $request->user()->id);

                $karya = $karyaId > 0
                    ? (clone $baseQuery)->where('id', $karyaId)->first()
                    : $baseQuery->first();
            }
        }

        $karyaDraft = null;
        if ($karya) {
            $karyaDraft = [
                'id' => $karya->id,
                'kategori' => $karya->nama_kategori,
                'namaKarya' => $karya->nama_karya,
                'waKetua' => $karya->wa_ketua,
                'dosenPembimbing' => $karya->dosen_pembimbing ?? [
                    'nik' => '',
                    'nama' => '',
                    'email' => '',
                    'bidang' => '',
                ],
                'anggotaTim' => $karya->anggota_tim ?? [],
                'linkTambahan' => $karya->link_tambahan ?? [],
                'proposalLink' => $karya->proposal_path,
                'lampiran' => $karya->lampiran->map(fn ($item) => [
                    'id' => $item->id,
                    'file' => null,
                    'namaFile' => $item->nama_asli,
                    'deskripsi' => $item->deskripsi,
                    'url' => route('peserta.daftar-karya.lampiran.preview', ['lampiran' => $item->id]),
                ])->values(),
            ];
        }

        $panduan = PanduanLomba::query()
            ->where('edisi_lomba_id', $edisiForm->id)
            ->first();

        return Inertia::render('Peserta/DaftarKarya/Index', [
            'daftarKategori' => $kategoriAktif->pluck('nama')->values(),
            'gemasiAktifLabel' => $edisiForm->nama . ' (' . $edisiForm->tahun . ')',
            'karyaDraft' => $karyaDraft,
            'pendaftaranDibuka' => $pendaftaranDibuka && !$isArsipReadOnly,
            'readOnly' => $isArsipReadOnly || $isMemberOnly,
            'isArsipReadOnly' => $isArsipReadOnly,
            'templateProposalUrl' => $panduan?->template_proposal_path ?: null,
            'templateProposalName' => $panduan?->template_proposal_nama_tampil,
        ]);
    }

    public function daftar(Request $request)
    {
        $edisiAktif = $this->resolveEdisiAktifOrFail();
        $pendaftaranDibuka = $this->pendaftaranMasihDibuka($edisiAktif);
        $userEmail = $this->normalizedEmail($request->user()->email);
        $punyaKaryaArsip = KaryaPeserta::query()
            ->where(function ($query) use ($request, $userEmail) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$userEmail],
                    );
            })
            ->where(function ($query) {
                $query->whereNotNull('archived_at')
                    ->orWhereHas('edisi', function ($q) {
                        $q->where('status', 'arsip');
                    });
            })
            ->exists();

        $karya = KaryaPeserta::query()
            ->with(['lampiran', 'edisi'])
            ->where(function ($query) use ($request, $userEmail) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$userEmail],
                    );
            })
            ->whereNull('archived_at')
            ->whereHas('edisi', function ($query) {
                $query->where('status', '!=', 'arsip');
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($item) use ($edisiAktif, $pendaftaranDibuka, $request) {
                $anggota = collect($item->anggota_tim ?? []);
                $ketua = $anggota->firstWhere('peran', 'ketua');
                return [
                'id' => $item->id,
                'edisi_lomba_id' => $item->edisi_lomba_id,
                'nama_karya' => $item->nama_karya,
                'nama_kategori' => $item->nama_kategori,
                'jumlah_anggota_tim' => $anggota->count(),
                'nama_ketua' => $ketua['nama'] ?? null,
                'status_tampilan' => $item->status === 'submitted' ? 'Lengkap' : 'Tahap 1 tersimpan',
                'updated_at' => optional($item->updated_at)->toDateTimeString(),
                'edisi' => optional($item->edisi)->nama,
                'dapat_dikelola' => $pendaftaranDibuka
                    && ((int) $item->edisi_lomba_id === (int) $edisiAktif->id)
                    && (int) $item->user_id === (int) $request->user()->id,
                'peran_akses' => $this->resolvePeranAkses($item, $request->user()->email),
                ];
            })
            ->values();

        $arsipPendaftaran = KaryaPeserta::query()
            ->with('edisi')
            ->where(function ($query) use ($request, $userEmail) {
                $query->where('user_id', (int) $request->user()->id)
                    ->orWhereRaw(
                        "JSON_SEARCH(anggota_tim, 'one', ?, NULL, '$[*].email') IS NOT NULL",
                        [$userEmail],
                    );
            })
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->get()
            ->map(function ($item) use ($edisiAktif, $pendaftaranDibuka, $request) {
                $anggota = collect($item->anggota_tim ?? []);
                $ketua = $anggota->firstWhere('peran', 'ketua');
                return [
                    'id' => $item->id,
                    'edisi_lomba_id' => $item->edisi_lomba_id,
                    'nama_karya' => $item->nama_karya,
                    'nama_kategori' => $item->nama_kategori,
                    'jumlah_anggota_tim' => $anggota->count(),
                    'nama_ketua' => $ketua['nama'] ?? null,
                    'status_tampilan' => 'Diarsipkan',
                    'updated_at' => optional($item->archived_at)->toDateTimeString(),
                    'edisi' => optional($item->edisi)->nama,
                    'dapat_dikelola' => $pendaftaranDibuka
                        && ((int) $item->edisi_lomba_id === (int) $edisiAktif->id)
                        && (int) $item->user_id === (int) $request->user()->id,
                    'peran_akses' => $this->resolvePeranAkses($item, $request->user()->email),
                ];
            })
            ->values();

        return Inertia::render('Peserta/DaftarKarya/List', [
            'daftarKarya' => $karya,
            'arsipPendaftaran' => $arsipPendaftaran,
            'pendaftaranDibuka' => $pendaftaranDibuka,
            'punyaKaryaArsip' => $punyaKaryaArsip,
            'gemasiAktifLabel' => $edisiAktif->nama . ' (' . $edisiAktif->tahun . ')',
        ]);
    }

    public function restore(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless($this->pendaftaranMasihDibuka($edisi), 403, 'Pendaftaran sudah ditutup.');

        abort_unless(
            $this->canAccessKarya($request, $karya) && (int) $karya->edisi_lomba_id === (int) $edisi->id,
            403
        );

        if ($karya->archived_at) {
            $karya->archived_at = null;
            $karya->save();
        }

        return redirect()->route('peserta.daftar-karya')->with('success', 'Karya berhasil dipulihkan.')->setStatusCode(303);
    }

    public function simpanTahapSatu(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless($this->pendaftaranMasihDibuka($edisi), 403, 'Pendaftaran sudah ditutup.');

        $validated = $request->validate([
            'id' => 'nullable|integer',
            'kategori' => [
                'required',
                'string',
                Rule::exists('kategori_lomba', 'nama')
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('aktif', true),
            ],
            'namaKarya' => 'required|string|max:255',
            'waKetua' => 'required|string|max:30',
        ]);

        $karya = $this->persistDraft($request, $edisi, $validated);

        return redirect()
            ->back()
            ->with('karya_id', $karya->id)
            ->with('success', 'Draft tahap 1 berhasil disimpan.')
            ->setStatusCode(303);
    }

    public function simpanDraftPerTahap(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless($this->pendaftaranMasihDibuka($edisi), 403, 'Pendaftaran sudah ditutup.');

        $step = (int) $request->input('step', 1);
        abort_unless(in_array($step, [1, 2, 3], true), 422, 'Tahap draft tidak valid.');

        $rulesByStep = [
            1 => [
                'id' => 'nullable|integer',
                'step' => 'required|integer',
                'kategori' => [
                    'required',
                    'string',
                    Rule::exists('kategori_lomba', 'nama')
                        ->where('edisi_lomba_id', $edisi->id)
                        ->where('aktif', true),
                ],
                'namaKarya' => 'required|string|max:255',
                'waKetua' => 'required|string|max:30',
            ],
            2 => [
                'id' => 'nullable|integer',
                'step' => 'required|integer',
                'dosenPembimbing' => 'required|array',
                'dosenPembimbing.nik' => 'nullable|string|max:50',
                'dosenPembimbing.nama' => 'required|string|max:255',
                'dosenPembimbing.email' => 'required|email|max:255',
                'dosenPembimbing.bidang' => 'required|string|max:255',
                'anggotaTim' => 'required|array|min:1|max:6',
                'anggotaTim.*.nim' => 'required|string|max:50',
                'anggotaTim.*.nama' => 'required|string|max:255',
                'anggotaTim.*.email' => 'required|email|max:255',
                'anggotaTim.*.peran' => ['required', Rule::in(['ketua', 'anggota'])],
            ],
            3 => [
                'id' => 'nullable|integer',
                'step' => 'required|integer',
                'proposalLink' => 'required|string|max:2048',
                'linkTambahan' => 'nullable|array',
                'linkTambahan.*.label' => 'nullable|string|max:100',
                'linkTambahan.*.url' => 'nullable|string|max:2048',
            ],
        ];

        $validator = Validator::make($request->all(), $rulesByStep[$step]);
        $validator->setCustomMessages([
            'dosenPembimbing.email.email' => 'Email harus valid.',
            'anggotaTim.*.email.email' => 'Email harus valid.',
            'anggotaTim.*.nim.regex' => 'NIM hanya boleh angka.',
            'proposalLink.required' => 'Proposal wajib diisi.',
        ]);
        if ($step === 2) {
            $validator->after(function ($validator) use ($request) {
                $anggota = collect($request->input('anggotaTim', []));
                if ($anggota->where('peran', 'ketua')->count() !== 1) {
                    $validator->errors()->add('anggotaTim', 'Harus ada tepat satu ketua tim.');
                }
            });
        }

        $validated = $validator->validate();
        if ($step === 2) {
            $this->validateAnggotaTim($validated['anggotaTim']);
            $validated['anggotaTim'] = $this->normalizeAnggotaTim($validated['anggotaTim']);
        } elseif ($step === 3) {
            $validated['linkTambahan'] = collect($validated['linkTambahan'] ?? [])
                ->filter(function ($item) {
                    return is_array($item)
                        && (trim((string) ($item['label'] ?? '')) !== '' || trim((string) ($item['url'] ?? '')) !== '');
                })
                ->map(function ($item) {
                    return [
                        'label' => trim((string) ($item['label'] ?? '')),
                        'url' => trim((string) ($item['url'] ?? '')),
                    ];
                })
                ->values()
                ->all();
        }
        $karya = $this->persistDraft($request, $edisi, $validated);

        return redirect()
            ->back()
            ->with('karya_id', $karya->id)
            ->with('success', 'Draft tahap ' . $step . ' berhasil disimpan.')
            ->setStatusCode(303);
    }

    public function store(Request $request)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless($this->pendaftaranMasihDibuka($edisi), 403, 'Pendaftaran sudah ditutup.');
        $user = $request->user();
        $karyaId = (int) $request->input('id', 0);
        $karyaLama = null;
        if ($karyaId > 0) {
            $karyaLama = KaryaPeserta::query()
                ->with('lampiran')
                ->where('id', $karyaId)
                ->where('edisi_lomba_id', $edisi->id)
                ->where('user_id', (int) $user->id)
                ->first();
        }

        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'kategori' => [
                'required',
                'string',
                Rule::exists('kategori_lomba', 'nama')
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('aktif', true),
            ],
            'namaKarya' => 'required|string|max:255',
            'waKetua' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]+$/'],
            'dosenPembimbing' => 'required|array',
            'dosenPembimbing.nik' => ['nullable', 'string', 'max:50', 'regex:/^[0-9.]+$/'],
            'dosenPembimbing.nama' => 'required|string|max:255',
            'dosenPembimbing.email' => 'required|email|max:255',
            'dosenPembimbing.bidang' => 'required|string|max:255',
            'anggotaTim' => 'required|array|min:1|max:6',
            'anggotaTim.*.nim' => ['required', 'string', 'max:50', 'regex:/^[0-9.]+$/'],
            'anggotaTim.*.nama' => 'required|string|max:255',
            'anggotaTim.*.email' => 'required|email|max:255',
            'anggotaTim.*.peran' => ['required', Rule::in(['ketua', 'anggota'])],
            'proposalLink' => 'required|string|max:2048',
            'linkTambahan' => 'nullable|array',
            'linkTambahan.*.label' => 'nullable|string|max:100',
            'linkTambahan.*.url' => 'nullable|string|max:2048',
            'lampiran' => 'nullable|array',
            'lampiran.*.id' => 'nullable|integer',
            'lampiran.*.deskripsi' => 'nullable|string',
            'lampiran.*.file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,ppt,pptx|max:5120',
        ], [
            'kategori.required' => 'Kategori wajib diisi.',
            'namaKarya.required' => 'Nama karya wajib diisi.',
            'waKetua.required' => 'Nomor WA wajib diisi.',
            'waKetua.regex' => 'Nomor WA harus angka.',
            'dosenPembimbing.email.required' => 'Email dosen pembimbing wajib diisi.',
            'dosenPembimbing.email.email' => 'Email harus valid.',
            'anggotaTim.*.nim.required' => 'NIM wajib diisi.',
            'anggotaTim.*.nim.regex' => 'NIM hanya boleh angka.',
            'anggotaTim.*.email.required' => 'Email anggota wajib diisi.',
            'anggotaTim.*.email.email' => 'Email harus valid.',
            'proposalLink.required' => 'Proposal wajib diisi.',
        ]);

        $validator->after(function ($validator) use ($request, $karyaLama) {
            $anggota = collect($request->input('anggotaTim', []));
            if ($anggota->where('peran', 'ketua')->count() !== 1) {
            $validator->errors()->add('anggotaTim', 'Harus ada tepat satu ketua tim.');
        }

    });

        $validated = $validator->validate();
        $this->validateAnggotaTim($validated['anggotaTim']);
        $validated['anggotaTim'] = $this->normalizeAnggotaTim($validated['anggotaTim']);

        $kategori = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('nama', $validated['kategori'])
            ->first();

        DB::transaction(function () use ($request, $validated, $edisi, $user, $kategori, $karyaLama) {
            $karya = $karyaLama ?: new KaryaPeserta();
            $karya->fill([
                'edisi_lomba_id' => $edisi->id,
                'user_id' => $user->id,
                'kategori_lomba_id' => $kategori?->id,
                'nama_kategori' => $validated['kategori'],
                'nama_karya' => $validated['namaKarya'],
                'wa_ketua' => $validated['waKetua'],
                'dosen_pembimbing' => $validated['dosenPembimbing'] ?? null,
                'anggota_tim' => $validated['anggotaTim'],
                'link_tambahan' => $validated['linkTambahan'] ?? [],
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            $karya->save();

            if ($request->has('proposalLink')) {
                $link = $request->input('proposalLink');
                $karya->proposal_path = $link ? $link : null;
                $karya->save();
            }

            $lampiranInput = collect($request->input('lampiran', []));
            $keepIds = [];

            foreach ($lampiranInput as $index => $item) {
                $lampiranId = isset($item['id']) ? (int) $item['id'] : null;
                $deskripsi = trim((string) ($item['deskripsi'] ?? ''));
                $uploaded = $request->file("lampiran.$index.file");

                if ($lampiranId) {
                    $lampiran = LampiranKaryaPeserta::query()
                        ->where('karya_peserta_id', $karya->id)
                        ->where('id', $lampiranId)
                        ->first();

                    if (!$lampiran) {
                        continue;
                    }

                    if ($uploaded) {
                        Storage::disk('public')->delete($lampiran->path_file);
                        $path = $uploaded->store("karya-peserta/{$edisi->id}/{$user->id}", 'public');
                        $lampiran->path_file = $path;
                        $lampiran->nama_asli = $uploaded->getClientOriginalName();
                        $lampiran->mime_type = $uploaded->getClientMimeType();
                        $lampiran->ukuran = (int) $uploaded->getSize();
                    }

                    $lampiran->deskripsi = $deskripsi;
                    $lampiran->urutan = $index + 1;
                    $lampiran->save();
                    $keepIds[] = $lampiran->id;
                    continue;
                }

                if (!$uploaded) {
                    continue;
                }

                $path = $uploaded->store("karya-peserta/{$edisi->id}/{$user->id}", 'public');
                $baru = LampiranKaryaPeserta::create([
                    'karya_peserta_id' => $karya->id,
                    'path_file' => $path,
                    'nama_asli' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'ukuran' => (int) $uploaded->getSize(),
                    'deskripsi' => $deskripsi,
                    'urutan' => $index + 1,
                ]);
                $keepIds[] = $baru->id;
            }

            $hapusLampiran = LampiranKaryaPeserta::query()
                ->where('karya_peserta_id', $karya->id)
                ->when(!empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
                ->get();

            foreach ($hapusLampiran as $item) {
                Storage::disk('public')->delete($item->path_file);
                $item->delete();
            }
        });

        return redirect()->route('peserta.daftar-karya')->with('success', 'Karya berhasil di-submit.')->setStatusCode(303);
    }

    public function destroy(Request $request, KaryaPeserta $karya)
    {
        $edisi = $this->resolveEdisiAktifOrFail();
        abort_unless($this->pendaftaranMasihDibuka($edisi), 403, 'Pendaftaran sudah ditutup.');

        abort_unless(
            $this->canAccessKarya($request, $karya) && (int) $karya->edisi_lomba_id === (int) $edisi->id,
            403
        );

        if (!$karya->archived_at) {
            $karya->archived_at = now();
            $karya->save();
        }

        return redirect()->route('peserta.daftar-karya')->with('success', 'Karya berhasil diarsipkan.')->setStatusCode(303);
    }

    public function previewLampiran(Request $request, LampiranKaryaPeserta $lampiran)
    {
        $karya = $lampiran->karya;
        abort_unless($karya, 404);

        abort_unless($this->canAccessKarya($request, $karya), 403);

        abort_unless(Storage::disk('public')->exists($lampiran->path_file), 404);

        return Storage::disk('public')->response(
            $lampiran->path_file,
            $lampiran->nama_asli,
            ['Content-Type' => $lampiran->mime_type ?: 'application/octet-stream']
        );
    }

    public function previewProposal(Request $request, KaryaPeserta $karya)
    {
        abort_unless(
            $this->canAccessKarya($request, $karya),
            403
        );

        abort_unless($karya->proposal_path, 404);

        if (str_starts_with($karya->proposal_path, 'http')) {
            return redirect()->away($karya->proposal_path);
        }

        abort_unless(Storage::disk('public')->exists($karya->proposal_path), 404);

        return Storage::disk('public')->response(
            $karya->proposal_path,
            $karya->proposal_nama_asli ?? 'proposal',
            ['Content-Type' => $karya->proposal_mime_type ?: 'application/octet-stream']
        );
    }
}
