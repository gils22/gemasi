<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $edisiIds = DB::table('timeline_lomba')
            ->select('edisi_lomba_id')
            ->distinct()
            ->pluck('edisi_lomba_id');

        foreach ($edisiIds as $edisiId) {
            $alreadyExists = DB::table('timeline_lomba')
                ->where('edisi_lomba_id', $edisiId)
                ->where('fase_kunci', 'pengumuman_nominasi')
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DB::table('timeline_lomba')
                ->where('edisi_lomba_id', $edisiId)
                ->where('urutan', '>=', 4)
                ->increment('urutan');

            DB::table('timeline_lomba')->insert([
                'edisi_lomba_id' => $edisiId,
                'judul' => 'Pengumuman Nominasi',
                'tipe' => 'utama',
                'fase_kunci' => 'pengumuman_nominasi',
                'mulai_pada' => null,
                'selesai_pada' => null,
                'is_tba' => true,
                'deskripsi' => null,
                'urutan' => 4,
                'aktif' => true,
                'nominasi_email_queued_at' => null,
                'nominasi_email_sent_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $edisiIds = DB::table('timeline_lomba')
            ->select('edisi_lomba_id')
            ->distinct()
            ->pluck('edisi_lomba_id');

        foreach ($edisiIds as $edisiId) {
            DB::table('timeline_lomba')
                ->where('edisi_lomba_id', $edisiId)
                ->where('fase_kunci', 'pengumuman_nominasi')
                ->delete();

            DB::table('timeline_lomba')
                ->where('edisi_lomba_id', $edisiId)
                ->where('urutan', '>', 4)
                ->decrement('urutan');
        }
    }
};
