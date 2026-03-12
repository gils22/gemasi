<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SinkronisasiUserLegacySeeder extends Seeder
{
    public function run(): void
    {
        $edisiAktif = Edition::query()->where('aktif', true)->first();
        if (!$edisiAktif) {
            $edisiAktif = Edition::query()->where('tahun', (int) now()->format('Y'))->first();
        }
        if (!$edisiAktif) {
            $edisiAktif = Edition::query()->orderByDesc('tahun')->first();
        }

        if (!$edisiAktif) {
            $this->command?->warn('Sinkronisasi dibatalkan: edisi lomba belum tersedia.');
            return;
        }

        $rolePeserta = Role::query()->where('name', 'peserta')->first();
        if (!$rolePeserta) {
            $this->command?->warn('Sinkronisasi dibatalkan: role peserta belum tersedia.');
            return;
        }

        $totalSinkron = 0;

        User::query()->with('roles:id,name')->chunkById(200, function ($users) use ($edisiAktif, $rolePeserta, &$totalSinkron) {
            foreach ($users as $user) {
                $roleIds = $user->roles->pluck('id')->values();

                // User hasil input manual phpMyAdmin sering belum punya role.
                if ($roleIds->isEmpty()) {
                    $user->roles()->syncWithoutDetaching([$rolePeserta->id]);
                    $roleIds = collect([$rolePeserta->id]);
                }

                $payload = $roleIds
                    ->map(function ($roleId) use ($edisiAktif, $user) {
                        return [
                            'edisi_lomba_id' => $edisiAktif->id,
                            'user_id' => $user->id,
                            'role_id' => $roleId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })
                    ->all();

                if (!empty($payload)) {
                    DB::table('edisi_lomba_user_role')->upsert(
                        $payload,
                        ['edisi_lomba_id', 'user_id', 'role_id'],
                        ['updated_at']
                    );
                    $totalSinkron += count($payload);
                }
            }
        });

        $this->command?->info("Sinkronisasi selesai. Relasi edisi-role terproses: {$totalSinkron} baris.");
    }
}

