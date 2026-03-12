<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LampiranKaryaPeserta extends Model
{
    protected $table = 'lampiran_karya_peserta';

    protected $fillable = [
        'karya_peserta_id',
        'path_file',
        'nama_asli',
        'mime_type',
        'ukuran',
        'deskripsi',
        'urutan',
    ];

    public function karya()
    {
        return $this->belongsTo(KaryaPeserta::class, 'karya_peserta_id');
    }
}
