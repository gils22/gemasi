<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('proposal_path')->nullable()->after('link_pameran_ar');
            $table->string('proposal_nama_asli')->nullable()->after('proposal_path');
            $table->string('proposal_mime_type')->nullable()->after('proposal_nama_asli');
            $table->unsignedBigInteger('proposal_ukuran')->nullable()->after('proposal_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_path',
                'proposal_nama_asli',
                'proposal_mime_type',
                'proposal_ukuran',
            ]);
        });
    }
};
