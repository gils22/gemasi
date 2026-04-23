<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function editions()
    {
        return $this->belongsToMany(
            Edition::class,
            'edisi_lomba_user_role',
            'user_id',
            'edisi_lomba_id'
        )
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function editionRoles()
    {
        return $this->belongsToMany(
            Role::class,
            'edisi_lomba_user_role',
            'user_id',
            'role_id'
        )
            ->withPivot('edisi_lomba_id')
            ->withTimestamps();
    }

    public function hasRole($role)
    {
        if ($this->isSuperadmin()) {
            return true;
        }
        return $this->roles->contains('name', $role);
    }

    public function hasRoleInEdition(string $role, int $editionId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }
        return $this->editionRoles()
            ->where('roles.name', $role)
            ->wherePivot('edisi_lomba_id', $editionId)
            ->exists();
    }

    public function isSuperadmin(): bool
    {
        $email = strtolower(trim((string) ($this->email ?? '')));
        if ($email === '') {
            return false;
        }

        $allowed = (array) config('superadmin.emails', []);
        $allowed = array_values(array_filter(array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $allowed)));

        return in_array($email, $allowed, true);
    }
}
