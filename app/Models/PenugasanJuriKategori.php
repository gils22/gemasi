<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenugasanJuriKategori extends Model
{
    protected $table = 'edisi_lomba_kategori_juri';

    protected $fillable = [
        'edisi_lomba_id',
        'kategori_lomba_id',
        'juri_id',
        'tahap',
    ];

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriLomba::class, 'kategori_lomba_id');
    }

    public function juri()
    {
        return $this->belongsTo(User::class, 'juri_id');
    }
}
