<?php

namespace App\Http\Middleware;

use App\Models\Edition;
use App\Services\EditionStatusSynchronizer;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Str;

class HandleInertiaRequests extends Middleware
{
    private function resolveRoleKonteks(Request $request): ?string
    {
        $segmenPertama = $request->segment(1);
        if (in_array($segmenPertama, ['admin', 'juri', 'peserta'], true)) {
            return $segmenPertama;
        }

        return null;
    }

    private function daftarEdisiSesuaiRole(Request $request, ?string $roleKonteks)
    {
        $user = $request->user();
        if (!$user) {
            return collect();
        }

        $query = Edition::query()->orderByDesc('tahun');

        if ($roleKonteks === 'admin') {
            return $query->get(['id', 'nama', 'tahun', 'status', 'aktif']);
        }

        if (in_array($roleKonteks, ['juri', 'peserta'], true)) {
            return $query
                ->whereHas('roles', function ($q) use ($user, $roleKonteks) {
                    $q->where('roles.name', $roleKonteks)
                        ->where('edisi_lomba_user_role.user_id', $user->id);
                })
                ->get(['id', 'nama', 'tahun', 'status', 'aktif']);
        }

        return collect();
    }

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        app(EditionStatusSynchronizer::class)->archiveExpiredEditions();

        $roleKonteks = $this->resolveRoleKonteks($request);
        $daftarEdisi = $this->daftarEdisiSesuaiRole($request, $roleKonteks);
        $user = $request->user();

        $tahunSekarang = (int) now()->format('Y');
        $selectedId = (int) session('edisi_aktif_id');
        $edisiAktif = $daftarEdisi->firstWhere('id', $selectedId)
            ?? $daftarEdisi->firstWhere('status', 'aktif')
            ?? $daftarEdisi->firstWhere('aktif', true)
            ?? $daftarEdisi->firstWhere('tahun', $tahunSekarang)
            ?? $daftarEdisi->first();

        if ($edisiAktif && session('edisi_aktif_id') !== $edisiAktif->id) {
            session(['edisi_aktif_id' => $edisiAktif->id]);
        }

        $resolvedName = $user?->name ?: Str::before($user?->email ?? '', '@');
        return [
            ...parent::share($request),

            'app' => [
                'logo' => asset('favicon.ico'),
            ],
            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $resolvedName,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ]
                    : null,

                'role' => $roleKonteks ?? $user?->roles()->value('name'),
            ],

            'edisi' => [
                'aktif' => $edisiAktif
                    ? [
                        'id' => $edisiAktif->id,
                        'nama' => $edisiAktif->nama,
                        'tahun' => $edisiAktif->tahun,
                        'status' => $edisiAktif->status,
                        'aktif' => (bool) $edisiAktif->aktif,
                    ]
                    : null,
                'daftar' => $daftarEdisi->values(),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'karya_id' => fn() => $request->session()->get('karya_id'),
            ],
        ];
    }
}
