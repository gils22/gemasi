<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Account/Info', [
            'account' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'avatar' => $user?->avatar,
                'has_password' => !empty($user?->password),
            ],
            'requirePasswordSetup' => $request->query('setup') === '1' && empty($user?->password),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $hasPassword = !empty($user?->password);

        $validated = $request->validate([
            'current_password' => [$hasPassword ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        if ($hasPassword && !Hash::check($validated['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ])->setStatusCode(303);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->back()->with('success', $hasPassword
            ? 'Password berhasil diperbarui.'
            : 'Password berhasil dibuat.'
        )->setStatusCode(303);
    }
}
