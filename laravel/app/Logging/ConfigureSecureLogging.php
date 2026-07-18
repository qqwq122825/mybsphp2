<?php

namespace App\Logging;

use App\Support\SensitiveDataRedactor;
use Illuminate\Log\Logger;

final class ConfigureSecureLogging
{
    public function __construct(
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->pushProcessor(
            new RedactSensitiveContext($this->redactor),
        );
    }
}
