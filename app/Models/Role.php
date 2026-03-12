<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'label'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function editions()
    {
        return $this->belongsToMany(
            Edition::class,
            'edisi_lomba_user_role',
            'role_id',
            'edisi_lomba_id'
        )
            ->withPivot('user_id')
            ->withTimestamps();
    }
}
