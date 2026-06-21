<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_settings', 'login_carousel_items')) {
                $table->json('login_carousel_items')->nullable()->after('video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            if (Schema::hasColumn('landing_settings', 'login_carousel_items')) {
                $table->dropColumn('login_carousel_items');
            }
        });
    }
};
