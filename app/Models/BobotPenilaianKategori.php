<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BobotPenilaianKategori extends Model
{
    use HasFactory;

    protected $table = 'bobot_penilaian_kategori';

    protected $fillable = [
        'edisi_lomba_id',
        'kategori_lomba_id',
        'persentase',
        'catatan',
    ];

    protected $casts = [
        'persentase' => 'float',
    ];

    public function edisi(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriLomba::class, 'kategori_lomba_id');
    }
}

