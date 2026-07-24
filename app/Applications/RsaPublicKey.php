<?php

namespace App\Applications;

use InvalidArgumentException;

final class RsaPublicKey
{
    /**
     * Convert either a PEM PKCS#1 public key or its Base64 body to one line.
     */
    public static function compact(string $publicKey): string
    {
        $body = str_replace([
            '-----BEGIN RSA PUBLIC KEY-----',
            '-----END RSA PUBLIC KEY-----',
        ], '', trim($publicKey));
        $body = preg_replace('/\s+/', '', $body) ?? '';

        if ($body === '' || base64_decode($body, true) === false) {
            throw new InvalidArgumentException('服务器 RSA 公钥格式错误。');
        }

        return $body;
    }

    /**
     * OpenSSL expects a PEM envelope, while clients store/copy only the body.
     */
    public static function pem(string $publicKey): string
    {
        return "-----BEGIN RSA PUBLIC KEY-----\n"
            .chunk_split(self::compact($publicKey), 64, "\n")
            ."-----END RSA PUBLIC KEY-----\n";
    }
}
