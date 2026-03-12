<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_karya_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karya_peserta_id')->constrained('karya_peserta')->cascadeOnDelete();
            $table->string('path_file');
            $table->string('nama_asli');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->text('deskripsi');
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->index(['karya_peserta_id', 'urutan'], 'lampiran_karya_urutan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_karya_peserta');
    }
};
