<?php
declare(strict_types=1);

namespace App\Modules\Auth;

final class LoginRateLimiter
{
    public function __construct(
        private readonly int $maximumAttempts = 5,
        private readonly int $windowSeconds = 900
    ) {
    }

    public function available(string $key): bool
    {
        $attempts = $this->attempts($key);
        return count($attempts) < $this->maximumAttempts;
    }

    public function hit(string $key): void
    {
        $attempts = $this->attempts($key);
        $attempts[] = time();
        $_SESSION['_login_attempts'][$this->hash($key)] = $attempts;
    }

    public function clear(string $key): void
    {
        unset($_SESSION['_login_attempts'][$this->hash($key)]);
    }

    public function retryAfter(string $key): int
    {
        $attempts = $this->attempts($key);
        if (count($attempts) < $this->maximumAttempts) {
            return 0;
        }
        return max(1, $this->windowSeconds - (time() - min($attempts)));
    }

    /** @return list<int> */
    private function attempts(string $key): array
    {
        $hash = $this->hash($key);
        $attempts = $_SESSION['_login_attempts'][$hash] ?? [];
        if (!is_array($attempts)) {
            $attempts = [];
        }
        $cutoff = time() - $this->windowSeconds;
        $attempts = array_values(array_filter($attempts, static fn ($at): bool => is_int($at) && $at > $cutoff));
        $_SESSION['_login_attempts'][$hash] = $attempts;
        return $attempts;
    }

    private function hash(string $key): string
    {
        return hash('sha256', $key);
    }
}

