<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE karya_peserta MODIFY anggota_tim JSON NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY analisa_pasar_kompetitor TEXT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY problem_bisnis TEXT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY problem_solution_fit TEXT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY deskripsi_business_model LONGTEXT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY tim_management TEXT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY link_demo VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE karya_peserta SET anggota_tim = JSON_ARRAY() WHERE anggota_tim IS NULL");
        DB::statement("UPDATE karya_peserta SET analisa_pasar_kompetitor = '' WHERE analisa_pasar_kompetitor IS NULL");
        DB::statement("UPDATE karya_peserta SET problem_bisnis = '' WHERE problem_bisnis IS NULL");
        DB::statement("UPDATE karya_peserta SET problem_solution_fit = '' WHERE problem_solution_fit IS NULL");
        DB::statement("UPDATE karya_peserta SET deskripsi_business_model = '' WHERE deskripsi_business_model IS NULL");
        DB::statement("UPDATE karya_peserta SET tim_management = '' WHERE tim_management IS NULL");
        DB::statement("UPDATE karya_peserta SET link_demo = '' WHERE link_demo IS NULL");

        DB::statement('ALTER TABLE karya_peserta MODIFY anggota_tim JSON NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY analisa_pasar_kompetitor TEXT NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY problem_bisnis TEXT NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY problem_solution_fit TEXT NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY deskripsi_business_model LONGTEXT NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY tim_management TEXT NOT NULL');
        DB::statement('ALTER TABLE karya_peserta MODIFY link_demo VARCHAR(255) NOT NULL');
    }
};
