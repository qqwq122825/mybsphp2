<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyCredential extends Model
{
    protected $guarded = [];

    protected $hidden = ['password_ciphertext'];

    protected function casts(): array
    {
        return ['password_ciphertext' => 'encrypted'];
    }
}
