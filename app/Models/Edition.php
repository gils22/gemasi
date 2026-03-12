<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edition extends Model
{
    protected $table = 'edisi_lomba';

    protected $fillable = [
        'nama',
        'tahun',
        'status',
        'aktif',
        'mulai_pada',
        'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'edisi_lomba_user_role',
            'edisi_lomba_id',
            'user_id'
        )
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'edisi_lomba_user_role',
            'edisi_lomba_id',
            'role_id'
        )
            ->withPivot('user_id')
            ->withTimestamps();
    }

    public function kategori()
    {
        return $this->hasMany(KategoriLomba::class, 'edisi_lomba_id');
    }

    public function timeline()
    {
        return $this->hasMany(TimelineLomba::class, 'edisi_lomba_id');
    }

    public function panduan()
    {
        return $this->hasOne(PanduanLomba::class, 'edisi_lomba_id');
    }

    public function bobotPenilaian()
    {
        return $this->hasMany(BobotPenilaianKategori::class, 'edisi_lomba_id');
    }
}
