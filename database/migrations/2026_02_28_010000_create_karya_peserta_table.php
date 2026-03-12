<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karya_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kategori_lomba_id')->nullable()->constrained('kategori_lomba')->nullOnDelete();
            $table->string('nama_kategori');
            $table->string('nama_karya');
            $table->string('wa_ketua', 30);
            $table->json('dosen_pembimbing')->nullable();
            $table->json('anggota_tim');
            $table->text('analisa_pasar_kompetitor');
            $table->text('problem_bisnis');
            $table->text('problem_solution_fit');
            $table->longText('deskripsi_business_model');
            $table->text('tim_management');
            $table->string('link_drive')->nullable();
            $table->string('link_demo');
            $table->string('link_pameran_ar')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['edisi_lomba_id', 'user_id'], 'karya_peserta_unik_edisi_user');
            $table->index(['edisi_lomba_id', 'status'], 'karya_peserta_edisi_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya_peserta');
    }
};
