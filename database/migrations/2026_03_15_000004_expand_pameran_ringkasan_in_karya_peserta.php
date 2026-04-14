<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE karya_peserta MODIFY pameran_ringkasan TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE karya_peserta MODIFY pameran_ringkasan VARCHAR(150) NULL');
    }
};
