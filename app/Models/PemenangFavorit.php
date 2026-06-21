<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemenangFavorit extends Model
{
    protected $table = 'pemenang_favorit';

    protected $fillable = [
        'edisi_lomba_id',
        'peringkat',
        'karya_peserta_id',
        'catatan',
    ];

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function karya()
    {
        return $this->belongsTo(KaryaPeserta::class, 'karya_peserta_id');
    }
}
