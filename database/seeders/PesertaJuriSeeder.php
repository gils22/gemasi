<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PesertaJuriSeeder extends Seeder
{
    public function run(): void
    {
        $tahunSekarang = (int) now()->format('Y');
        $edisiSekarang = Edition::query()->firstOrCreate(
            ['tahun' => $tahunSekarang],
            [
                'nama' => 'GEMASI ' . $tahunSekarang,
                'status' => 'aktif',
                'aktif' => true,
            ]
        );

        $edisiArsip = Edition::query()->firstOrCreate(
            ['tahun' => $tahunSekarang - 1],
            [
                'nama' => 'GEMASI ' . ($tahunSekarang - 1),
                'status' => 'arsip',
                'aktif' => false,
            ]
        );

        $rolePeserta = Role::query()->where('name', 'peserta')->firstOrFail();
        $pesertaData = [
            ['name' => 'Peserta 01', 'email' => 'peserta01@students.amikom.ac.id'],
            ['name' => 'Peserta 02', 'email' => 'peserta02@students.amikom.ac.id'],
            ['name' => 'Peserta 03', 'email' => 'peserta03@students.amikom.ac.id'],
            ['name' => 'Peserta 04', 'email' => 'peserta04@students.amikom.ac.id'],
            ['name' => 'Peserta 05', 'email' => 'peserta05@students.amikom.ac.id'],
            ['name' => 'Peserta 06', 'email' => 'peserta06@students.amikom.ac.id'],
            ['name' => 'Peserta 07', 'email' => 'peserta07@students.amikom.ac.id'],
            ['name' => 'Peserta 08', 'email' => 'peserta08@students.amikom.ac.id'],
        ];

        foreach ($pesertaData as $index => $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => null,
                ]
            );

            $user->roles()->syncWithoutDetaching([$rolePeserta->id]);

            $this->upsertRoleEdisi($edisiSekarang->id, $user->id, $rolePeserta->id);

            // Dua peserta pertama juga punya riwayat tahun sebelumnya (untuk uji arsip peserta).
            if ($index < 2) {
                $this->upsertRoleEdisi($edisiArsip->id, $user->id, $rolePeserta->id);
            }
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
