<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminJuriSeeder extends Seeder
{
    public function run(): void
    {
        $edisiAktif = Edition::query()
            ->where('status', 'aktif')
            ->orWhere('aktif', true)
            ->orderByDesc('tahun')
            ->first()
            ?? Edition::query()->orderByDesc('tahun')->first();

        if (!$edisiAktif) {
            return;
        }

        $roleAdmin = Role::query()->where('name', 'admin')->firstOrFail();
        $roleJuri = Role::query()->where('name', 'juri')->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'si@amikom.ac.id'],
            [
                'name' => 'Admin GEMASI',
                'password' => 'gemasiamikom123',
            ]
        );

        $admin->roles()->syncWithoutDetaching([$roleAdmin->id]);
        $this->upsertRoleEdisi($edisiAktif->id, $admin->id, $roleAdmin->id);

        $juriData = [
            [
                'name' => 'Juri GEMASI',
                'email' => 'jurigemasi@amikom.ac.id',
                'password' => 'jurigemasi123',
            ],
            ['name' => 'Juri 01', 'email' => 'juri01@amikom.ac.id'],
            ['name' => 'Juri 02', 'email' => 'juri02@amikom.ac.id'],
            ['name' => 'Juri 03', 'email' => 'juri03@amikom.ac.id'],
        ];

        foreach ($juriData as $data) {
            $juri = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'] ?? null,
                ]
            );

            $juri->roles()->syncWithoutDetaching([$roleJuri->id]);
            $this->upsertRoleEdisi($edisiAktif->id, $juri->id, $roleJuri->id);
        }
    }

    private function upsertRoleEdisi(int $edisiId, int $userId, int $roleId): void
    {
        DB::table('edisi_lomba_user_role')->upsert(
            [[
                'edisi_lomba_id' => $edisiId,
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['edisi_lomba_id', 'user_id', 'role_id'],
            ['updated_at']
        );
    }
}
