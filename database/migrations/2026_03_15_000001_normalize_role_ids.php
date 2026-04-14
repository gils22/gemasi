<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $now = now();

        $ensureRole = function (string $name, string $label) use ($now): int {
            $existingId = DB::table('roles')->where('name', $name)->value('id');
            if ($existingId) {
                return (int) $existingId;
            }

            return (int) DB::table('roles')->insertGetId([
                'name' => $name,
                'label' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $currentAdminId = $ensureRole('admin', 'Admin');
        $currentJuriId = $ensureRole('juri', 'Juri');
        $currentPesertaId = $ensureRole('peserta', 'Peserta');

        $desired = [
            'admin' => 1,
            'juri' => 2,
            'peserta' => 3,
        ];

        $maxId = (int) DB::table('roles')->max('id');
        $base = $maxId + 100;
        $temp = [
            'admin' => $base + 1,
            'juri' => $base + 2,
            'peserta' => $base + 3,
        ];

        $map = [
            'admin' => $currentAdminId,
            'juri' => $currentJuriId,
            'peserta' => $currentPesertaId,
        ];

        foreach ($map as $name => $oldId) {
            DB::table('roles')->where('id', $oldId)->update(['id' => $temp[$name]]);
            DB::table('role_user')->where('role_id', $oldId)->update(['role_id' => $temp[$name]]);
            DB::table('edisi_lomba_user_role')->where('role_id', $oldId)->update(['role_id' => $temp[$name]]);
        }

        foreach ($desired as $name => $newId) {
            DB::table('roles')->where('id', $temp[$name])->update(['id' => $newId]);
            DB::table('role_user')->where('role_id', $temp[$name])->update(['role_id' => $newId]);
            DB::table('edisi_lomba_user_role')->where('role_id', $temp[$name])->update(['role_id' => $newId]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No-op: intentionally not reverting role ids.
    }
};
