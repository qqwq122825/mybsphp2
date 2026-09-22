<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProxyLink extends Model
{
    protected $guarded = [];

    protected $hidden = ['token_ciphertext'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean', 'token_ciphertext' => 'encrypted',
            'previous_path_until' => 'immutable_datetime', 'last_sync_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function address(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->path;
    }
}
