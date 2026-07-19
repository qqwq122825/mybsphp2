<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id',
    'appid_param',
    'signature_param',
    'payload_param',
    'api_param',
    'credential_param',
    'credential_secret_param',
    'device_param',
    'appsafecode_param',
    'login_api_name',
    'max_clock_skew_seconds',
    'legacy_enabled',
    'extra_config',
])]
class ApplicationLegacyConfig extends Model
{
    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected function casts(): array
    {
        return [
            'max_clock_skew_seconds' => 'integer',
            'legacy_enabled' => 'boolean',
            'extra_config' => 'array',
        ];
    }
}
