<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperadmin') || !$user->isSuperadmin()) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}

