<?php

namespace App\Logging;

use App\Support\SensitiveDataRedactor;
use Monolog\LogRecord;

final class RedactSensitiveContext
{
    public function __construct(
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactor->redactMessage($record->message),
            context: $this->redactor->redact($record->context),
            extra: $this->redactor->redact($record->extra),
        );
    }
}
