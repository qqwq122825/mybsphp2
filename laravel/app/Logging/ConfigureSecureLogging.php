<?php

namespace App\Logging;

use App\Support\SensitiveDataRedactor;
use Illuminate\Log\Logger;
use LogicException;
use Monolog\Logger as MonologLogger;

final class ConfigureSecureLogging
{
    public function __construct(
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof MonologLogger) {
            throw new LogicException('Secure logging requires a Monolog logger.');
        }

        $monolog->pushProcessor(
            new RedactSensitiveContext($this->redactor),
        );
    }
}
