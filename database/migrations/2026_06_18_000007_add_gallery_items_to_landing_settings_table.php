<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_settings', 'gallery_items')) {
                $table->json('gallery_items')->nullable()->after('login_carousel_items');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_settings', function (Blueprint $table) {
            if (Schema::hasColumn('landing_settings', 'gallery_items')) {
                $table->dropColumn('gallery_items');
            }
        });
    }
};
