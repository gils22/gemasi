<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            if (Schema::hasColumn('karya_peserta', 'pameran_link')) {
                $table->dropColumn(['pameran_link']);
            }

            if (!Schema::hasColumn('karya_peserta', 'pameran_file_path')) {
                $table->string('pameran_file_path')->nullable()->after('link_tambahan');
                $table->string('pameran_file_nama_asli')->nullable()->after('pameran_file_path');
                $table->string('pameran_file_mime_type')->nullable()->after('pameran_file_nama_asli');
                $table->unsignedBigInteger('pameran_file_ukuran')->nullable()->after('pameran_file_mime_type');
            }

            if (!Schema::hasColumn('karya_peserta', 'pameran_submitted_at')) {
                $table->timestamp('pameran_submitted_at')->nullable()->after('pameran_file_ukuran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            if (!Schema::hasColumn('karya_peserta', 'pameran_link')) {
                $table->string('pameran_link')->nullable()->after('link_tambahan');
            }
        });
    }
};
