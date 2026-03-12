<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemenang_karya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('kategori_lomba_id')->constrained('kategori_lomba')->cascadeOnDelete();
            $table->foreignId('karya_peserta_id')->constrained('karya_peserta')->cascadeOnDelete();
            $table->unsignedTinyInteger('peringkat');
            $table->decimal('nilai_final', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['edisi_lomba_id', 'kategori_lomba_id', 'peringkat'], 'pemenang_peringkat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemenang_karya');
    }
};
