<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }

            if (method_exists($user, 'editionRoles')) {
                $edisiId = (int) session('edisi_aktif_id', 0);

                $hasEditionRole = $user->editionRoles()
                    ->when(
                        $edisiId > 0,
                        fn ($q) => $q->where('edisi_lomba_user_role.edisi_lomba_id', $edisiId),
                    )
                    ->where('roles.name', $role)
                    ->exists();

                if ($hasEditionRole) {
                    return $next($request);
                }
            }
        }

        abort(403, 'Akses ditolak.');
    }
}
