<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\User;
use App\Models\Role;
use App\Models\KategoriLomba;
use App\Models\PenugasanJuriKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function resolveEdisiKonteks(Request $request): Edition
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

        abort_if(!$edisi, 500, 'Edisi aktif belum tersedia.');

        session(['edisi_aktif_id' => $edisi->id]);
        return $edisi;
    }

    private function pastikanEdisiBukanArsip(Edition $edisi): void
    {
        abort_if($edisi->status === 'arsip', 403, 'Mode arsip hanya bisa dibaca.');
    }

    /*
    |--------------------------------------------------------------------------
    | PESERTA
    |--------------------------------------------------------------------------
     */
    public function peserta(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);

        $users = User::query()
            ->with('roles')
            ->whereHas('editionRoles', function ($q) use ($edisi) {
                $q->where('roles.name', 'peserta')
                    ->where('edisi_lomba_user_role.edisi_lomba_id', $edisi->id);
            })
            ->whereDoesntHave('editionRoles', function ($q) use ($edisi) {
                $q->whereIn('roles.name', ['admin', 'juri'])
                    ->where('edisi_lomba_user_role.edisi_lomba_id', $edisi->id);
            })
            ->latest()
            ->get();

        return Inertia::render('Admin/Users/Peserta', [
            'users' => $users,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | JURI
    |--------------------------------------------------------------------------
    */
    public function juri(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);

        $users = User::query()
            ->with('roles')
            ->whereHas('editionRoles', function ($q) use ($edisi) {
                $q->whereIn('roles.name', ['admin', 'juri'])
                    ->where('edisi_lomba_user_role.edisi_lomba_id', $edisi->id);
            })
            ->latest()
            ->get();

        $kategoriOptions = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (KategoriLomba $item) => [
                'id' => (int) $item->id,
                'nama' => $item->nama,
            ])
            ->values()
            ->all();

        $penugasanRows = PenugasanJuriKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->get(['juri_id', 'kategori_lomba_id', 'tahap'])
            ->groupBy('juri_id')
            ->map(function ($rows) {
                $tahap1 = $rows->where('tahap', 'tahap_1')
                    ->pluck('kategori_lomba_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $tahap2 = $rows->where('tahap', 'tahap_2')
                    ->pluck('kategori_lomba_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                return [
                    'tahap_1_ids' => $tahap1,
                    'tahap_2_ids' => $tahap2,
                ];
            });

        $users = $users->map(function (User $user) use ($penugasanRows) {
            $penugasan = $penugasanRows->get((int) $user->id, []);
            $tahap1 = $penugasan['tahap_1_ids'] ?? [];
            $tahap2 = $penugasan['tahap_2_ids'] ?? [];
            $user->setAttribute('juri_kategori_tahap_1_ids', $tahap1);
            $user->setAttribute('juri_kategori_tahap_2_ids', $tahap2);
            $user->setAttribute(
                'juri_tahap',
                !empty($tahap1) && !empty($tahap2)
                    ? 'tahap_1_2'
                    : (!empty($tahap1) ? 'tahap_1' : (!empty($tahap2) ? 'tahap_2' : null))
            );
            return $user;
        });

        return Inertia::render('Admin/Users/Juri', [
            'users' => $users,
            'kategoriOptions' => $kategoriOptions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (CREATE)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $this->pastikanEdisiBukanArsip($edisi);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'roles' => 'required|array|min:1',
            'roles.*' => ['required', 'string', Rule::in(['admin', 'juri', 'peserta'])],
            'juri_kategori_tahap_1_ids' => 'nullable|array',
            'juri_kategori_tahap_1_ids.*' => 'integer|exists:kategori_lomba,id',
            'juri_kategori_tahap_2_ids' => 'nullable|array',
            'juri_kategori_tahap_2_ids.*' => 'integer|exists:kategori_lomba,id',
        ]);

        $selectedRoles = collect($request->roles)->map(fn ($v) => (string) $v)->values();
        $isJuri = $selectedRoles->contains('juri');
        $juriTahap1Ids = collect($request->input('juri_kategori_tahap_1_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $juriTahap2Ids = collect($request->input('juri_kategori_tahap_2_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($isJuri) {
            $allowedKategoriIds = KategoriLomba::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('aktif', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($juriTahap1Ids->isEmpty() && $juriTahap2Ids->isEmpty()) {
                return back()->withErrors([
                    'juri_kategori_tahap_1_ids' => 'Pilih minimal 1 kategori untuk tahap 1 atau tahap 2.',
                ])->setStatusCode(303);
            }

            foreach ($juriTahap1Ids as $kategoriId) {
                if (!in_array($kategoriId, $allowedKategoriIds, true)) {
                    return back()->withErrors([
                        'juri_kategori_tahap_1_ids' => 'Kategori juri tahap 1 tidak valid pada edisi aktif.',
                    ])->setStatusCode(303);
                }
            }
            foreach ($juriTahap2Ids as $kategoriId) {
                if (!in_array($kategoriId, $allowedKategoriIds, true)) {
                    return back()->withErrors([
                        'juri_kategori_tahap_2_ids' => 'Kategori juri tahap 2 tidak valid pada edisi aktif.',
                    ])->setStatusCode(303);
                }
            }
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make('password'),
        ]);

        DB::transaction(function () use ($request, $edisi, $user, $juriTahap1Ids, $juriTahap2Ids, $isJuri) {
            $roleIds = Role::whereIn('name', $request->roles)->pluck('id');
            $user->roles()->sync($roleIds);
            $payloadPivot = $roleIds->map(fn ($roleId) => [
                'edisi_lomba_id' => $edisi->id,
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('edisi_lomba_user_role')->upsert(
                $payloadPivot,
                ['edisi_lomba_id', 'user_id', 'role_id'],
                ['updated_at']
            );

            PenugasanJuriKategori::query()
                ->where('edisi_lomba_id', $edisi->id)
                ->where('juri_id', $user->id)
                ->delete();

            if ($isJuri) {
                $payloadPenugasan = [];

                foreach ($juriTahap1Ids as $kategoriId) {
                    $jumlahTerpakai = PenugasanJuriKategori::query()
                        ->where('edisi_lomba_id', $edisi->id)
                        ->where('kategori_lomba_id', $kategoriId)
                        ->where('tahap', 'tahap_1')
                        ->count();
                    if ($jumlahTerpakai >= 2) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'juri_kategori_tahap_1_ids' => 'Salah satu kategori tahap 1 sudah memiliki 2 juri.',
                        ]);
                    }

                    $payloadPenugasan[] = [
                        'edisi_lomba_id' => $edisi->id,
                        'kategori_lomba_id' => $kategoriId,
                        'juri_id' => $user->id,
                        'tahap' => 'tahap_1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach ($juriTahap2Ids as $kategoriId) {
                    $jumlahTerpakai = PenugasanJuriKategori::query()
                        ->where('edisi_lomba_id', $edisi->id)
                        ->where('kategori_lomba_id', $kategoriId)
                        ->where('tahap', 'tahap_2')
                        ->count();
                    if ($jumlahTerpakai >= 2) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'juri_kategori_tahap_2_ids' => 'Salah satu kategori tahap 2 sudah memiliki 2 juri.',
                        ]);
                    }

                    $payloadPenugasan[] = [
                        'edisi_lomba_id' => $edisi->id,
                        'kategori_lomba_id' => $kategoriId,
                        'juri_id' => $user->id,
                        'tahap' => 'tahap_2',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($payloadPenugasan)) {
                    PenugasanJuriKategori::query()->insert($payloadPenugasan);
                }
            }
        });

        return redirect()->back()->setStatusCode(303);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, User $user)
{
    $edisi = $this->resolveEdisiKonteks($request);
    $this->pastikanEdisiBukanArsip($edisi);

    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'roles' => 'required|array|min:1',
        'roles.*' => ['required', 'string', Rule::in(['admin', 'juri', 'peserta'])],
        'juri_kategori_tahap_1_ids' => 'nullable|array',
        'juri_kategori_tahap_1_ids.*' => 'integer|exists:kategori_lomba,id',
        'juri_kategori_tahap_2_ids' => 'nullable|array',
        'juri_kategori_tahap_2_ids.*' => 'integer|exists:kategori_lomba,id',
    ]);

    $selectedRoles = collect($request->roles)->map(fn ($v) => (string) $v)->values();
    $isJuri = $selectedRoles->contains('juri');
    $juriTahap1Ids = collect($request->input('juri_kategori_tahap_1_ids', []))
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();
    $juriTahap2Ids = collect($request->input('juri_kategori_tahap_2_ids', []))
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    if ($isJuri) {
        $allowedKategoriIds = KategoriLomba::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('aktif', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($juriTahap1Ids->isEmpty() && $juriTahap2Ids->isEmpty()) {
            return back()->withErrors([
                'juri_kategori_tahap_1_ids' => 'Pilih minimal 1 kategori untuk tahap 1 atau tahap 2.',
            ])->setStatusCode(303);
        }

        foreach ($juriTahap1Ids as $kategoriId) {
            if (!in_array($kategoriId, $allowedKategoriIds, true)) {
                return back()->withErrors([
                    'juri_kategori_tahap_1_ids' => 'Kategori juri tahap 1 tidak valid pada edisi aktif.',
                ])->setStatusCode(303);
            }
        }
        foreach ($juriTahap2Ids as $kategoriId) {
            if (!in_array($kategoriId, $allowedKategoriIds, true)) {
                return back()->withErrors([
                    'juri_kategori_tahap_2_ids' => 'Kategori juri tahap 2 tidak valid pada edisi aktif.',
                ])->setStatusCode(303);
            }
        }
    }

    $user->update([
        'name'  => $request->name,
        'email' => $request->email,
    ]);

    DB::transaction(function () use ($request, $edisi, $user, $juriTahap1Ids, $juriTahap2Ids, $isJuri) {
        $roleIds = Role::whereIn('name', $request->roles)->pluck('id');
        $user->roles()->sync($roleIds);
        DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->where('user_id', $user->id)
            ->delete();

        $payloadPivot = $roleIds->map(fn ($roleId) => [
            'edisi_lomba_id' => $edisi->id,
            'user_id' => $user->id,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::table('edisi_lomba_user_role')->insert($payloadPivot);

        PenugasanJuriKategori::query()
            ->where('edisi_lomba_id', $edisi->id)
            ->where('juri_id', $user->id)
            ->delete();

        if ($isJuri) {
            $payloadPenugasan = [];

            foreach ($juriTahap1Ids as $kategoriId) {
                $jumlahTerpakai = PenugasanJuriKategori::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('kategori_lomba_id', $kategoriId)
                    ->where('tahap', 'tahap_1')
                    ->where('juri_id', '!=', $user->id)
                    ->count();
                if ($jumlahTerpakai >= 2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'juri_kategori_tahap_1_ids' => 'Salah satu kategori tahap 1 sudah memiliki 2 juri.',
                    ]);
                }

                $payloadPenugasan[] = [
                    'edisi_lomba_id' => $edisi->id,
                    'kategori_lomba_id' => $kategoriId,
                    'juri_id' => $user->id,
                    'tahap' => 'tahap_1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach ($juriTahap2Ids as $kategoriId) {
                $jumlahTerpakai = PenugasanJuriKategori::query()
                    ->where('edisi_lomba_id', $edisi->id)
                    ->where('kategori_lomba_id', $kategoriId)
                    ->where('tahap', 'tahap_2')
                    ->where('juri_id', '!=', $user->id)
                    ->count();
                if ($jumlahTerpakai >= 2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'juri_kategori_tahap_2_ids' => 'Salah satu kategori tahap 2 sudah memiliki 2 juri.',
                    ]);
                }

                $payloadPenugasan[] = [
                    'edisi_lomba_id' => $edisi->id,
                    'kategori_lomba_id' => $kategoriId,
                    'juri_id' => $user->id,
                    'tahap' => 'tahap_2',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($payloadPenugasan)) {
                PenugasanJuriKategori::query()->insert($payloadPenugasan);
            }
        }
    });

    return redirect()->back()->setStatusCode(303);
}

    /*
    |--------------------------------------------------------------------------
    | BULK DELETE
    |--------------------------------------------------------------------------
    */
    public function bulkDelete(Request $request)
    {
        $edisi = $this->resolveEdisiKonteks($request);
        $this->pastikanEdisiBukanArsip($edisi);

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users,id',
        ]);

        DB::table('edisi_lomba_user_role')
            ->where('edisi_lomba_id', $edisi->id)
            ->whereIn('user_id', $request->ids)
            ->where('user_id', '!=', auth()->id())
            ->delete();

        return redirect()->back()->setStatusCode(303);
    }

}
