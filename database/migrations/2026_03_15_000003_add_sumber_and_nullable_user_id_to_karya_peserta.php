<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('sumber', 20)->default('peserta')->after('status');
        });

        DB::statement('ALTER TABLE karya_peserta MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE karya_peserta MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn('sumber');
        });
    }
};
