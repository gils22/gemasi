<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panduan_lomba', function (Blueprint $table) {
            $table->dropColumn([
                'tautan_pdf',
                'template_proposal_nama_asli',
                'template_proposal_mime_type',
                'template_proposal_ukuran',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('panduan_lomba', function (Blueprint $table) {
            $table->string('tautan_pdf')->nullable();
            $table->string('template_proposal_nama_asli')->nullable();
            $table->string('template_proposal_mime_type')->nullable();
            $table->unsignedBigInteger('template_proposal_ukuran')->nullable();
        });
    }
};
