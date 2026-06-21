<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kategori_lomba', function (Blueprint $table) {
            $table->string('icon_path')->nullable()->after('deskripsi');
            $table->string('icon_nama_asli')->nullable()->after('icon_path');
            $table->string('icon_mime_type')->nullable()->after('icon_nama_asli');
            $table->unsignedInteger('icon_ukuran')->nullable()->after('icon_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_lomba', function (Blueprint $table) {
            $table->dropColumn([
                'icon_path',
                'icon_nama_asli',
                'icon_mime_type',
                'icon_ukuran',
            ]);
        });
    }
};
