<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('panduan_lomba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
            $table->longText('ketentuan_umum')->nullable();
            $table->string('tautan_pdf')->nullable();
            $table->timestamps();

            $table->unique('edisi_lomba_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panduan_lomba');
    }
};

