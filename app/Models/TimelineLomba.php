<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineLomba extends Model
{
    protected $table = 'timeline_lomba';

    protected $fillable = [
        'edisi_lomba_id',
        'judul',
        'tipe',
        'fase_kunci',
        'mulai_pada',
        'selesai_pada',
        'is_tba',
        'deskripsi',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'is_tba' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }
}

