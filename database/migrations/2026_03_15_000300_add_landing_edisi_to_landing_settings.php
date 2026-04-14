<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            $table
                ->foreignId('landing_edisi_lomba_id')
                ->nullable()
                ->constrained('edisi_lomba')
                ->nullOnDelete()
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            $table->dropForeign(['landing_edisi_lomba_id']);
            $table->dropColumn('landing_edisi_lomba_id');
        });
    }
};
