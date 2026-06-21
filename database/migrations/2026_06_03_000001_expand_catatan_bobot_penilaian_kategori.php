<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE `bobot_penilaian_kategori` MODIFY `catatan` TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `bobot_penilaian_kategori` MODIFY `catatan` VARCHAR(255) NULL');
    }
};
