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
                'proposal_nama_asli',
                'proposal_mime_type',
                'proposal_ukuran',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('proposal_nama_asli')->nullable();
            $table->string('proposal_mime_type')->nullable();
            $table->unsignedBigInteger('proposal_ukuran')->nullable();
        });
    }
};
