<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edisi_lomba_kategori_juri', function (Blueprint $table) {
            $table->string('tahap', 20)->default('tahap_2')->after('juri_id');
            $table->index(['edisi_lomba_id', 'tahap'], 'edisi_tahap_idx');
        });
    }

    public function down(): void
    {
        Schema::table('edisi_lomba_kategori_juri', function (Blueprint $table) {
            $table->dropIndex('edisi_tahap_idx');
            $table->dropColumn('tahap');
        });
    }
};
