<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('pameran_logo_path')->nullable()->after('pameran_file_path');
            $table->string('pameran_logo_nama_asli')->nullable()->after('pameran_logo_path');
            $table->string('pameran_logo_mime_type')->nullable()->after('pameran_logo_nama_asli');
            $table->unsignedBigInteger('pameran_logo_ukuran')->nullable()->after('pameran_logo_mime_type');
            $table->string('pameran_link_video')->nullable()->after('pameran_logo_ukuran');
            $table->string('pameran_ringkasan', 150)->nullable()->after('pameran_link_video');
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn([
                'pameran_logo_path',
                'pameran_logo_nama_asli',
                'pameran_logo_mime_type',
                'pameran_logo_ukuran',
                'pameran_link_video',
                'pameran_ringkasan',
            ]);
        });
    }
};
