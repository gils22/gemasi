<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kategori_lomba')) {
            return;
        }

        if (!Schema::hasColumn('kategori_lomba', 'urutan')) {
            return;
        }

        Schema::table('kategori_lomba', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('kategori_lomba')) {
            return;
        }

        if (Schema::hasColumn('kategori_lomba', 'urutan')) {
            return;
        }

        Schema::table('kategori_lomba', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('deskripsi');
        });
    }
};

