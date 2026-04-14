<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryaPeserta extends Model
{
    protected $table = 'karya_peserta';

    protected $fillable = [
        'edisi_lomba_id',
        'user_id',
        'kategori_lomba_id',
        'nama_kategori',
        'nama_karya',
        'wa_ketua',
        'dosen_pembimbing',
        'anggota_tim',
        'link_tambahan',
        'pameran_logo_path',
        'pameran_logo_nama_asli',
        'pameran_logo_mime_type',
        'pameran_logo_ukuran',
        'pameran_link_video',
        'pameran_ringkasan',
        'pameran_submitted_at',
        'proposal_path',
        'nilai_tahap_1',
        'catatan_tahap_1',
        'status',
        'sumber',
        'lolos_nominasi',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'dosen_pembimbing' => 'array',
            'anggota_tim' => 'array',
            'link_tambahan' => 'array',
            'lolos_nominasi' => 'boolean',
            'submitted_at' => 'datetime',
            'pameran_submitted_at' => 'datetime',
        ];
    }

    public function edisi()
    {
        return $this->belongsTo(Edition::class, 'edisi_lomba_id');
    }

    public function peserta()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriLomba::class, 'kategori_lomba_id');
    }

    public function lampiran()
    {
        return $this->hasMany(LampiranKaryaPeserta::class, 'karya_peserta_id')->orderBy('urutan');
    }
}
