<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superadminId = DB::table('roles')->where('name', 'superadmin')->value('id');
        if (!$superadminId) {
            return;
        }

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        if (!$adminId) {
            $adminId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'label' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $superadminUsers = DB::table('role_user')
            ->where('role_id', $superadminId)
            ->pluck('user_id');

        foreach ($superadminUsers as $userId) {
            $exists = DB::table('role_user')
                ->where('role_id', $adminId)
                ->where('user_id', $userId)
                ->exists();

            if (!$exists) {
                DB::table('role_user')->insert([
                    'role_id' => $adminId,
                    'user_id' => $userId,
                ]);
            }
        }

        DB::table('edisi_lomba_user_role')
            ->where('role_id', $superadminId)
            ->update(['role_id' => $adminId]);

        DB::table('role_user')->where('role_id', $superadminId)->delete();
        DB::table('roles')->where('id', $superadminId)->delete();
    }

    public function down(): void
    {
        // Intentional no-op to avoid reintroducing superadmin role.
    }
};
