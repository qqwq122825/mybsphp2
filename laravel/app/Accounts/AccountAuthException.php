<?php

namespace App\Accounts;

use RuntimeException;

final class AccountAuthException extends RuntimeException
{
    /** @param array<string, mixed>|null $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }
}
