<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EdisiKonteksController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'edisi_id' => 'required|integer|exists:edisi_lomba,id',
        ]);

        $edisiId = (int) $validated['edisi_id'];

        if (!$user->hasRole('admin')) {
            $hasAccess = DB::table('edisi_lomba_user_role')
                ->where('edisi_lomba_id', $edisiId)
                ->where('user_id', $user->id)
                ->exists();

            abort_unless($hasAccess, 403, 'Tidak memiliki akses ke edisi ini.');
        }

        $edisi = Edition::query()->find($edisiId);
        abort_unless($edisi, 404);

        session(['edisi_aktif_id' => $edisi->id]);

        return redirect()->back()->with('success', 'Edisi aktif berhasil diganti.');
    }
}
