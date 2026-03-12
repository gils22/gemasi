<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemenangKarya extends Model
{
    protected $table = 'pemenang_karya';

    protected $fillable = [
        'edisi_lomba_id',
        'kategori_lomba_id',
        'karya_peserta_id',
        'peringkat',
        'nilai_final',
    ];

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriLomba::class, 'kategori_lomba_id');
    }

    public function karya()
    {
        return $this->belongsTo(KaryaPeserta::class, 'karya_peserta_id');
    }
}
