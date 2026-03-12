<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai_tahap_1')->nullable()->after('proposal_path');
            $table->text('catatan_tahap_1')->nullable()->after('nilai_tahap_1');
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->dropColumn(['nilai_tahap_1', 'catatan_tahap_1']);
        });
    }
};
