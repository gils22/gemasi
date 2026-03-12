<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenilaianTahapDua extends Model
{
    protected $table = 'penilaian_tahap_dua';

    protected $fillable = [
        'edisi_lomba_id',
        'karya_peserta_id',
        'juri_id',
        'rincian_nilai',
        'total_nilai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'rincian_nilai' => 'array',
            'total_nilai' => 'float',
        ];
    }

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function karya()
    {
        return $this->belongsTo(KaryaPeserta::class, 'karya_peserta_id');
    }

    public function juri()
    {
        return $this->belongsTo(User::class, 'juri_id');
    }
}
