<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_lomba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->string('judul');
            $table->enum('tipe', ['utama', 'tambahan'])->default('utama');
            $table->string('fase_kunci')->nullable();
            $table->dateTime('mulai_pada')->nullable();
            $table->dateTime('selesai_pada')->nullable();
            $table->boolean('is_tba')->default(true);
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['edisi_lomba_id', 'urutan'], 'timeline_edisi_urutan_idx');
            $table->index(['edisi_lomba_id', 'aktif'], 'timeline_edisi_aktif_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_lomba');
    }
};

