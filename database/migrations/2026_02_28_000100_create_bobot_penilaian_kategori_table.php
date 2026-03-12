<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bobot_penilaian_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->foreignId('kategori_lomba_id')->constrained('kategori_lomba')->cascadeOnDelete();
            $table->decimal('persentase', 5, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['edisi_lomba_id', 'kategori_lomba_id'], 'uniq_bobot_per_edisi_kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bobot_penilaian_kategori');
    }
};

