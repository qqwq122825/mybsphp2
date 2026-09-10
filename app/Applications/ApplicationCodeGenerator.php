<?php

namespace App\Applications;

use App\Models\Application;
use Random\Randomizer;
use RuntimeException;

final class ApplicationCodeGenerator
{
    public function __construct(private readonly Randomizer $random = new Randomizer) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = (string) $this->random->getInt(10000000, 99999999);

            // Soft-deleted applications also retain their globally unique code.
            // This is a suggestion, not a reservation; store validation stays authoritative.
            if (! Application::withTrashed()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('生成软件代号暂未成功，请刷新页面重试。');
    }
}
