<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalSecurityBlock extends Model
{
    protected $fillable = [
        'subject_type', 'subject_hash', 'subject_value', 'reason', 'note', 'administrator_id',
    ];

    public static function hashValue(string $value): string
    {
        return hash('sha256', trim($value));
    }

    public static function deviceBlocked(string $deviceId): bool
    {
        $normalized = trim($deviceId);
        if ($normalized === '') {
            return false;
        }

        return static::query()
            ->where('subject_type', 'device')
            ->where('subject_hash', static::hashValue($normalized))
            ->exists();
    }

    public static function ipBlocked(string $ip): bool
    {
        $normalized = trim($ip);
        if ($normalized === '') {
            return false;
        }

        return static::query()
            ->where('subject_type', 'ip')
            ->where('subject_hash', static::hashValue($normalized))
            ->exists();
    }

    /** @return BelongsTo<Administrator, $this> */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
