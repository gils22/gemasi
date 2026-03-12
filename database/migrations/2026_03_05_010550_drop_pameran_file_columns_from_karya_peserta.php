<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn([
                'pameran_file_path',
                'pameran_file_nama_asli',
                'pameran_file_mime_type',
                'pameran_file_ukuran',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('pameran_file_path')->nullable();
            $table->string('pameran_file_nama_asli')->nullable();
            $table->string('pameran_file_mime_type')->nullable();
            $table->unsignedBigInteger('pameran_file_ukuran')->nullable();
        });
    }
};
