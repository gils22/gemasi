<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_lomba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->string('nama');
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['edisi_lomba_id', 'nama'], 'kategori_edisi_nama_unik');
            $table->index(['edisi_lomba_id', 'aktif'], 'kategori_edisi_aktif_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_lomba');
    }
};

