<?php

namespace Database\Seeders;

use App\Models\LandingSetting;
use Illuminate\Database\Seeder;

class LandingSettingSeeder extends Seeder
{
    public function run(): void
    {
        LandingSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'hero_badge' => 'GEMASI 2026',
                'hero_title' => 'Beyond Innovation',
                'hero_subtitle' => 'Gelar Karya Mahasiswa Sistem Informasi Universitas Amikom Yogyakarta',
                'about_text' => 'Gelar Karya Mahasiswa Sistem Informasi (GEMASI) adalah ajang kompetisi yang mewadahi karya inovatif mahasiswa dari hasil final project mata kuliah. GEMASI membangun atmosfer kompetisi yang mendorong kreativitas, inovasi, serta membuka peluang jejaring untuk berkembang ke tingkat nasional dan internasional.',
                'cta_badge' => 'GEMASI 2026 - Beyond Innovation',
                'cta_label' => 'Daftar Sekarang',
                'cta_url' => '/login',
                'faq_items' => [
                    [
                        'q' => 'Siapa saja yang boleh mengikuti GEMASI?',
                        'a' => 'Peserta adalah mahasiswa Prodi Sistem Informasi sesuai ketentuan pada edisi yang aktif.',
                    ],
                    [
                        'q' => 'Apakah kategori lomba selalu sama setiap tahun?',
                        'a' => 'Tidak. Kategori dapat berubah mengikuti tema dan edisi yang sedang berjalan.',
                    ],
                    [
                        'q' => 'Bagaimana cara mengunggah karya?',
                        'a' => 'Unggah karya melalui dashboard peserta pada menu Daftar Karya sesuai tahapan yang tersedia.',
                    ],
                    [
                        'q' => 'Kapan pengumuman nominasi dan pemenang?',
                        'a' => 'Jadwal pengumuman mengikuti timeline resmi dan diperbarui oleh panitia.',
                    ],
                    [
                        'q' => 'Di mana saya bisa melihat panduan lengkap?',
                        'a' => 'Panduan tersedia pada halaman Panduan di menu atas landing.',
                    ],
                ],
            ],
        );
    }
}
