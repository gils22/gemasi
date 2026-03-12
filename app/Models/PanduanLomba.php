<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanduanLomba extends Model
{
    use HasFactory;

    protected $table = 'panduan_lomba';

    protected $fillable = [
        'edisi_lomba_id',
        'ketentuan_umum',
        'template_proposal_path',
        'template_proposal_nama_tampil',
    ];

    public function edisi(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }
}
