<?php

namespace App\Support;

use App\Models\SystemSetting;
use Throwable;

final class OperationalMode
{
    public const PRODUCTION = 'production';

    public const DEBUG = 'debug';

    public function current(): string
    {
        try {
            $mode = (string) SystemSetting::query()->whereKey('operational_mode')->value('value');

            return $mode === self::DEBUG ? self::DEBUG : self::PRODUCTION;
        } catch (Throwable) {
            return self::PRODUCTION;
        }
    }

    public function isDebug(): bool
    {
        return $this->current() === self::DEBUG;
    }
}
