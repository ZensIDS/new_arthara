<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'username', 'role', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }
}
