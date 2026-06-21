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
        'aktif',
        'nominasi_email_queued_at',
        'nominasi_email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'is_tba' => 'boolean',
            'aktif' => 'boolean',
            'nominasi_email_queued_at' => 'datetime',
            'nominasi_email_sent_at' => 'datetime',
        ];
    }

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }
}
