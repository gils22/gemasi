<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriLomba extends Model
{
    protected $table = 'kategori_lomba';

    protected $fillable = [
        'edisi_lomba_id',
        'nama',
        'slug',
        'deskripsi',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function bobotPenilaian()
    {
        return $this->hasMany(BobotPenilaianKategori::class, 'kategori_lomba_id');
    }
}
