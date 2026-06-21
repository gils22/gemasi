<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timeline_lomba', function (Blueprint $table) {
            if (!Schema::hasColumn('timeline_lomba', 'nominasi_email_queued_at')) {
                $table->timestamp('nominasi_email_queued_at')->nullable()->after('aktif');
            }

            if (!Schema::hasColumn('timeline_lomba', 'nominasi_email_sent_at')) {
                $table->timestamp('nominasi_email_sent_at')->nullable()->after('nominasi_email_queued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timeline_lomba', function (Blueprint $table) {
            if (Schema::hasColumn('timeline_lomba', 'nominasi_email_sent_at')) {
                $table->dropColumn('nominasi_email_sent_at');
            }

            if (Schema::hasColumn('timeline_lomba', 'nominasi_email_queued_at')) {
                $table->dropColumn('nominasi_email_queued_at');
            }
        });
    }
};
