<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemenang_arsip', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')
                ->constrained('edisi_lomba')
                ->cascadeOnDelete();
            $table->string('kategori');
            $table->unsignedTinyInteger('peringkat');
            $table->string('nama_karya');
            $table->json('anggota_tim')->nullable();
            $table->timestamps();

            $table->unique(
                ['edisi_lomba_id', 'kategori', 'peringkat'],
                'pemenang_arsip_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemenang_arsip');
    }
};
