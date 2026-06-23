<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edisi_lomba_kategori_juri', function (Blueprint $table) {
            $table->dropUnique('edisi_kategori_juri_unique');
            $table->unique(
                ['edisi_lomba_id', 'kategori_lomba_id', 'juri_id', 'tahap'],
                'edisi_kategori_juri_tahap_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('edisi_lomba_kategori_juri', function (Blueprint $table) {
            $table->dropUnique('edisi_kategori_juri_tahap_unique');
            $table->unique(
                ['edisi_lomba_id', 'kategori_lomba_id', 'juri_id'],
                'edisi_kategori_juri_unique',
            );
        });
    }
};
