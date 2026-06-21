<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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
            ],
        ]);
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Silakan pilih foto profil.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'avatar.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        $file = $validated['avatar'];
        $path = $file->store('avatars', 'public');

        $old = (string) ($user?->avatar ?? '');
        if ($old !== '' && !str_starts_with($old, 'http://') && !str_starts_with($old, 'https://')) {
            Storage::disk('public')->delete($old);
        }

        $user->update([
            'avatar' => $path,
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil diperbarui.')->setStatusCode(303);
    }
}
