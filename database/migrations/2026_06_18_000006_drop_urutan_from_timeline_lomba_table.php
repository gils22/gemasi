<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timeline_lomba', function (Blueprint $table) {
            if (Schema::hasColumn('timeline_lomba', 'urutan')) {
                $table->dropIndex('timeline_edisi_urutan_idx');
                $table->dropColumn('urutan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timeline_lomba', function (Blueprint $table) {
            if (!Schema::hasColumn('timeline_lomba', 'urutan')) {
                $table->unsignedInteger('urutan')->default(0)->after('is_tba');
                $table->index(['edisi_lomba_id', 'urutan'], 'timeline_edisi_urutan_idx');
            }
        });
    }
};
