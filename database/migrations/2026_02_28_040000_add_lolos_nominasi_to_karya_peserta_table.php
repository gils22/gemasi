<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->boolean('lolos_nominasi')->default(false)->after('status');
            $table->index(['edisi_lomba_id', 'lolos_nominasi'], 'karya_peserta_edisi_nominasi_idx');
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropIndex('karya_peserta_edisi_nominasi_idx');
            $table->dropColumn('lolos_nominasi');
        });
    }
};
