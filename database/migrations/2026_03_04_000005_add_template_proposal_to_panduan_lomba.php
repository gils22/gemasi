<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panduan_lomba', function (Blueprint $table) {
            $table->string('template_proposal_path')->nullable()->after('tautan_pdf');
            $table->string('template_proposal_nama_asli')->nullable()->after('template_proposal_path');
            $table->string('template_proposal_mime_type')->nullable()->after('template_proposal_nama_asli');
            $table->unsignedBigInteger('template_proposal_ukuran')->nullable()->after('template_proposal_mime_type');
            $table->string('template_proposal_nama_tampil')->nullable()->after('template_proposal_ukuran');
        });
    }

    public function down(): void
    {
        Schema::table('panduan_lomba', function (Blueprint $table) {
            $table->dropColumn([
                'template_proposal_path',
                'template_proposal_nama_asli',
                'template_proposal_mime_type',
                'template_proposal_ukuran',
                'template_proposal_nama_tampil',
            ]);
        });
    }
};
