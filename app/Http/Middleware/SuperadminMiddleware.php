<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('SUPERADMIN CHECK', [
            'url' => $request->fullUrl(),
            'auth' => auth()->check(),
            'user' => auth()->user()?->email,
            'is_superadmin' => auth()->user()?->isSuperadmin(),
        ]);

        $user = $request->user();

        abort_unless(
            $user && $user->isSuperadmin(),
            403,
            'Akses ditolak.'
        );

        return $next($request);
    }
}