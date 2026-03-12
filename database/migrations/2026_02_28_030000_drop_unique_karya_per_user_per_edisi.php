<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE karya_peserta DROP INDEX karya_peserta_unik_edisi_user');
        } catch (\Throwable $e) {
            // Index may already be removed on some environments.
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE karya_peserta ADD UNIQUE karya_peserta_unik_edisi_user (edisi_lomba_id, user_id)');
        } catch (\Throwable $e) {
            // Ignore if index already exists.
        }
    }
};

