<?php

namespace Database\Seeders;

use App\Models\Edition;
use Illuminate\Database\Seeder;

class EditionSeeder extends Seeder
{
    public function run(): void
    {
        $tahunSekarang = (int) now()->format('Y');

        $editions = [
            ['nama' => 'GEMASI 2023', 'tahun' => 2023, 'status' => 'arsip', 'aktif' => false],
            ['nama' => 'GEMASI 2024', 'tahun' => 2024, 'status' => 'arsip', 'aktif' => false],
            ['nama' => 'GEMASI 2025', 'tahun' => 2025, 'status' => 'arsip', 'aktif' => false],
            ['nama' => 'GEMASI 2026', 'tahun' => 2026, 'status' => $tahunSekarang === 2026 ? 'aktif' : 'arsip', 'aktif' => $tahunSekarang === 2026],
            ['nama' => 'GEMASI 2027', 'tahun' => 2027, 'status' => $tahunSekarang === 2027 ? 'aktif' : 'draft', 'aktif' => $tahunSekarang === 2027],
        ];

        foreach ($editions as $edition) {
            Edition::updateOrCreate(
                ['tahun' => $edition['tahun']],
                $edition
            );
        }
    }
}
