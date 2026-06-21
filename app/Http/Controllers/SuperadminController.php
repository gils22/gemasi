<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SuperadminController extends Controller
{
    public function index(Request $request)
    {
        $role = strtolower(trim((string) $request->query('role', 'admin')));

        if (!in_array($role, ['admin', 'juri', 'peserta'], true)) {
            $role = 'admin';
        }

        $edisiId = (int) session('edisi_aktif_id');

        if ($edisiId <= 0) {
            $tahunSekarang = (int) now()->format('Y');

            $edisi = Edition::query()
                ->where('status', 'aktif')
                ->first()
                ?? Edition::query()->where('aktif', true)->first()
                ?? Edition::query()->where('tahun', $tahunSekarang)->first()
                ?? Edition::query()->orderByDesc('tahun')->first();

            if ($edisi) {
                $edisiId = (int) $edisi->id;
                session(['edisi_aktif_id' => $edisiId]);
            }
        }

        $users = User::query()
            ->when(
                $edisiId > 0,
                function ($query) use ($role, $edisiId) {
                    $query->whereHas('editionRoles', function ($q) use ($role, $edisiId) {
                        $q->where('roles.name', $role)
                            ->where('edisi_lomba_user_role.edisi_lomba_id', $edisiId);
                    });
                },
                function ($query) use ($role) {
                    $query->whereHas('roles', function ($r) use ($role) {
                        $r->where('roles.name', $role);
                    });
                }
            )
            ->orderBy('name')
            ->orderBy('email')
            ->get([
                'id',
                'name',
                'email',
                'avatar',
            ]);

        return Inertia::render('Superadmin/Impersonate', [
            'roleTarget' => $role,
            'users' => $users,
        ]);
    }

    public function pilihUser(Request $request)
    {
        $role = strtolower(trim((string) $request->query('role', '')));

        abort_unless(
            in_array($role, ['admin', 'juri', 'peserta'], true),
            404
        );

        return redirect()
            ->to('/superadmin?role=' . $role)
            ->setStatusCode(303);
    }

    public function impersonate(Request $request)
    {
        $payload = $request->validate([
            'role' => ['required', 'string', 'in:admin,juri,peserta'],
            'user_id' => ['required', 'integer'],
        ]);

        $edisiId = (int) session('edisi_aktif_id');

        $target = User::query()
            ->where('id', (int) $payload['user_id'])
            ->when(
                $edisiId > 0,
                function ($query) use ($payload, $edisiId) {
                    $query->whereHas('editionRoles', function ($q) use ($payload, $edisiId) {
                        $q->where('roles.name', $payload['role'])
                            ->where('edisi_lomba_user_role.edisi_lomba_id', $edisiId);
                    });
                },
                function ($query) use ($payload) {
                    $query->whereHas('roles', function ($r) use ($payload) {
                        $r->where('roles.name', $payload['role']);
                    });
                }
            )
            ->firstOrFail();

        $original = auth()->user();

        $request->session()->put(
            'superadmin_original_user_id',
            (int) $original->id
        );

        Auth::login($target);

        $request->session()->regenerate();

        return redirect('/redirect-role');
    }

    public function stopImpersonate(Request $request)
    {
        $originalId = (int) $request->session()->get(
            'superadmin_original_user_id',
            0
        );

        abort_unless($originalId > 0, 403);

        $original = User::findOrFail($originalId);

        Auth::login($original);

        $request->session()->forget(
            'superadmin_original_user_id'
        );

        $request->session()->regenerate();

        return redirect('/superadmin');
    }
}