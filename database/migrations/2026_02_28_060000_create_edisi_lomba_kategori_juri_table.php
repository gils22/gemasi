<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edisi_lomba_kategori_juri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('kategori_lomba_id')->constrained('kategori_lomba')->cascadeOnDelete();
            $table->foreignId('juri_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['edisi_lomba_id', 'kategori_lomba_id', 'juri_id'], 'edisi_kategori_juri_unique');
            $table->index(['edisi_lomba_id', 'juri_id'], 'edisi_juri_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edisi_lomba_kategori_juri');
    }
};
