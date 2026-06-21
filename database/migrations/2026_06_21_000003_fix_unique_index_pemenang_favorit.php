<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemenang_favorit', function (Blueprint $table) {
            $table->dropUnique('pemenang_favorit_edisi_unique');
            $table->unique(['edisi_lomba_id', 'peringkat'], 'pemenang_favorit_edisi_peringkat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pemenang_favorit', function (Blueprint $table) {
            $table->dropUnique('pemenang_favorit_edisi_peringkat_unique');
            $table->unique('edisi_lomba_id', 'pemenang_favorit_edisi_unique');
        });
    }
};
