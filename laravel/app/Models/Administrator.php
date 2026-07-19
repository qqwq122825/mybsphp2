<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'username',
    'password',
    'secondary_password',
    'secondary_password_required',
    'role',
    'status',
    'last_login_at',
    'last_login_ip_hash',
])]
#[Hidden(['password', 'secondary_password'])]
class Administrator extends Authenticatable
{
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'secondary_password' => 'hashed',
            'secondary_password_required' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }
}
