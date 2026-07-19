<?php

namespace App\Cards;

final class CardCredentialHasher
{
    public function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', trim($code)));
    }

    public function lookupHash(string $code): string
    {
        return hash_hmac('sha256', $this->normalizeCode($code), $this->key());
    }

    private function key(): string
    {
        $key = (string) config('app.key');

        return $key !== '' ? $key : 'mybsphp-local-card-lookup';
    }
}
