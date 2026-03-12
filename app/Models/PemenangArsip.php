<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Edition;

class PemenangArsip extends Model
{
    protected $table = 'pemenang_arsip';

    protected $fillable = [
        'edisi_lomba_id',
        'kategori',
        'peringkat',
        'nama_karya',
        'anggota_tim',
    ];

    protected $casts = [
        'anggota_tim' => 'array',
    ];

    public function edisi(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }
}
