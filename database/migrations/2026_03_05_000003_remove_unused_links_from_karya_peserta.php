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
                'link_drive',
                'link_demo',
                'link_demo_video',
                'link_pameran_ar',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('karya_peserta', function (Blueprint $table) {
            $table->string('link_drive')->nullable();
            $table->string('link_demo')->nullable();
            $table->string('link_demo_video')->nullable();
            $table->string('link_pameran_ar')->nullable();
        });
    }
};
