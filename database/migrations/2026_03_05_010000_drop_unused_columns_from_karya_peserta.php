<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn([
                'analisa_pasar_kompetitor',
                'problem_bisnis',
                'problem_solution_fit',
                'deskripsi_business_model',
                'tim_management',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->text('analisa_pasar_kompetitor')->nullable();
            $table->text('problem_bisnis')->nullable();
            $table->text('problem_solution_fit')->nullable();
            $table->longText('deskripsi_business_model')->nullable();
            $table->text('tim_management')->nullable();
        });
    }
};
