<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaian_tahap_dua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('karya_peserta_id')->constrained('karya_peserta')->cascadeOnDelete();
            $table->foreignId('juri_id')->constrained('users')->cascadeOnDelete();
            $table->json('rincian_nilai');
            $table->decimal('total_nilai', 8, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['karya_peserta_id', 'juri_id'], 'penilaian_karya_juri_unique');
            $table->index(['edisi_lomba_id', 'juri_id'], 'penilaian_edisi_juri_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian_tahap_dua');
    }
};
