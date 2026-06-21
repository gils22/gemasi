<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemenang_favorit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('karya_peserta_id')->constrained('karya_peserta')->cascadeOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique('edisi_lomba_id', 'pemenang_favorit_edisi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemenang_favorit');
    }
};
