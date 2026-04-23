<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;

    protected $table = 'dosen';

    protected $fillable = [
        'nik',
        'nama',
        'email',
        'bidang',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}

