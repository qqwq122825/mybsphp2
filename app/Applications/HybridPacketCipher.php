<?php

namespace App\Applications;

use App\Models\Application;

final class HybridPacketCipher
{
    public const ALGORITHM = 'RSA-OAEP+A256CBC-HS256';

    /**
     * Client-side reference implementation used by tests and protocol tooling.
     *
     * @return array{v: int, alg: string, ts: int, nonce: string, ek: string, iv: string, data: string, mac: string, auth: string}
     */
    public function sealRequest(Application $application, string $plaintext, ?int $timestamp = null): array
    {
        return $this->sealRequestWithContext($application, $plaintext, $timestamp)['envelope'];
    }

    /**
     * @return array{
     *     envelope: array{v: int, alg: string, ts: int, nonce: string, ek: string, iv: string, data: string, mac: string, auth: string},
     *     session_key: string,
     *     request_nonce: string,
     * }
     */
    public function sealRequestWithContext(
        Application $application,
        string $plaintext,
        ?int $timestamp = null,
    ): array {
        $sessionKey = random_bytes(32);
        $wrappedKey = '';
        $encrypted = openssl_public_encrypt(
            $sessionKey,
            $wrappedKey,
            RsaPublicKey::pem((string) $application->server_public_key),
            OPENSSL_PKCS1_OAEP_PADDING,
        );

        if (! $encrypted) {
            throw new PacketCipherException('RSA 会话密钥封装失败。');
        }

        $timestamp ??= time();
        $nonce = $this->base64UrlEncode(random_bytes(16));
        $iv = random_bytes(16);
        $ciphertext = $this->aesEncrypt($plaintext, $this->encryptionKey($sessionKey), $iv);

        $envelope = [
            'v' => 2,
            'alg' => self::ALGORITHM,
            'ts' => $timestamp,
            'nonce' => $nonce,
            'ek' => $this->base64UrlEncode($wrappedKey),
            'iv' => $this->base64UrlEncode($iv),
            'data' => $this->base64UrlEncode($ciphertext),
        ];
        $canonical = $this->requestCanonical($envelope);
        $envelope['mac'] = $this->base64UrlEncode(hash_hmac('sha256', $canonical, $this->macKey($sessionKey), true));
        $envelope['auth'] = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $canonical.'|'.$envelope['mac'],
            $application->communicationKey(),
            true,
        ));

        return [
            'envelope' => $envelope,
            'session_key' => $sessionKey,
            'request_nonce' => $nonce,
        ];
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array{plaintext: string, session_key: string, request_nonce: string, timestamp: int}
     */
    public function openRequest(Application $application, array $envelope, int $maxClockSkew = 300): array
    {
        $request = $this->validatedRequestEnvelope($envelope, $maxClockSkew);
        $canonical = $this->requestCanonical($request);
        $expectedAuth = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $canonical.'|'.$request['mac'],
            $application->communicationKey(),
            true,
        ));

        if (! hash_equals($expectedAuth, $request['auth'])) {
            throw new PacketCipherException('通信认证 Key 校验失败。');
        }

        $sessionKey = '';
        $decrypted = openssl_private_decrypt(
            $this->base64UrlDecode($request['ek']),
            $sessionKey,
            $application->serverPrivateKey(),
            OPENSSL_PKCS1_OAEP_PADDING,
        );
        if (! $decrypted || strlen($sessionKey) !== 32) {
            throw new PacketCipherException('RSA 会话密钥解封失败。');
        }

        $expectedMac = $this->base64UrlEncode(hash_hmac('sha256', $canonical, $this->macKey($sessionKey), true));
        if (! hash_equals($expectedMac, $request['mac'])) {
            throw new PacketCipherException('数据包完整性校验失败。');
        }

        return [
            'plaintext' => $this->aesDecrypt(
                $this->base64UrlDecode($request['data']),
                $this->encryptionKey($sessionKey),
                $this->base64UrlDecode($request['iv']),
            ),
            'session_key' => $sessionKey,
            'request_nonce' => $request['nonce'],
            'timestamp' => $request['ts'],
        ];
    }

    /**
     * @return array{v: int, alg: string, direction: string, ts: int, nonce: string, request_nonce: string, iv: string, data: string, mac: string}
     */
    public function sealResponse(
        string $plaintext,
        string $sessionKey,
        string $requestNonce,
        ?int $timestamp = null,
    ): array {
        if (strlen($sessionKey) !== 32) {
            throw new PacketCipherException('会话密钥长度错误。');
        }

        $timestamp ??= time();
        $iv = random_bytes(16);
        $response = [
            'v' => 2,
            'alg' => self::ALGORITHM,
            'direction' => 'response',
            'ts' => $timestamp,
            'nonce' => $this->base64UrlEncode(random_bytes(16)),
            'request_nonce' => $requestNonce,
            'iv' => $this->base64UrlEncode($iv),
            'data' => $this->base64UrlEncode($this->aesEncrypt(
                $plaintext,
                $this->encryptionKey($sessionKey),
                $iv,
            )),
        ];
        $response['mac'] = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $this->responseCanonical($response),
            $this->macKey($sessionKey),
            true,
        ));

        return $response;
    }

    /**
     * Client-side reference implementation used by tests and protocol tooling.
     *
     * @param  array<string, mixed>  $envelope
     */
    public function openResponse(array $envelope, string $sessionKey, string $requestNonce): string
    {
        $response = $this->validatedResponseEnvelope($envelope, $requestNonce);
        $expectedMac = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $this->responseCanonical($response),
            $this->macKey($sessionKey),
            true,
        ));
        if (! hash_equals($expectedMac, $response['mac'])) {
            throw new PacketCipherException('响应数据包完整性校验失败。');
        }

        return $this->aesDecrypt(
            $this->base64UrlDecode($response['data']),
            $this->encryptionKey($sessionKey),
            $this->base64UrlDecode($response['iv']),
        );
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array{v: int, alg: string, ts: int, nonce: string, ek: string, iv: string, data: string, mac: string, auth: string}
     */
    private function validatedRequestEnvelope(array $envelope, int $maxClockSkew): array
    {
        foreach (['alg', 'nonce', 'ek', 'iv', 'data', 'mac', 'auth'] as $field) {
            if (! is_string($envelope[$field] ?? null) || $envelope[$field] === '') {
                throw new PacketCipherException('数据包字段缺失：'.$field);
            }
        }
        if (($envelope['v'] ?? null) !== 2 || $envelope['alg'] !== self::ALGORITHM) {
            throw new PacketCipherException('数据包版本或算法不受支持。');
        }
        if (! is_int($envelope['ts'] ?? null)) {
            throw new PacketCipherException('数据包时间戳格式错误。');
        }
        if (abs(time() - $envelope['ts']) > $maxClockSkew) {
            throw new PacketCipherException('数据包已超过允许时间窗口。');
        }

        return [
            'v' => 2,
            'alg' => $envelope['alg'],
            'ts' => $envelope['ts'],
            'nonce' => $envelope['nonce'],
            'ek' => $envelope['ek'],
            'iv' => $envelope['iv'],
            'data' => $envelope['data'],
            'mac' => $envelope['mac'],
            'auth' => $envelope['auth'],
        ];
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array{v: int, alg: string, direction: string, ts: int, nonce: string, request_nonce: string, iv: string, data: string, mac: string}
     */
    private function validatedResponseEnvelope(array $envelope, string $requestNonce): array
    {
        foreach (['alg', 'direction', 'nonce', 'request_nonce', 'iv', 'data', 'mac'] as $field) {
            if (! is_string($envelope[$field] ?? null) || $envelope[$field] === '') {
                throw new PacketCipherException('响应字段缺失：'.$field);
            }
        }
        if (
            ($envelope['v'] ?? null) !== 2
            || $envelope['alg'] !== self::ALGORITHM
            || $envelope['direction'] !== 'response'
            || ! hash_equals($requestNonce, $envelope['request_nonce'])
            || ! is_int($envelope['ts'] ?? null)
        ) {
            throw new PacketCipherException('响应数据包上下文不匹配。');
        }

        return [
            'v' => 2,
            'alg' => $envelope['alg'],
            'direction' => 'response',
            'ts' => $envelope['ts'],
            'nonce' => $envelope['nonce'],
            'request_nonce' => $envelope['request_nonce'],
            'iv' => $envelope['iv'],
            'data' => $envelope['data'],
            'mac' => $envelope['mac'],
        ];
    }

    /** @param array{v: int, alg: string, ts: int, nonce: string, ek: string, iv: string, data: string} $envelope */
    private function requestCanonical(array $envelope): string
    {
        return implode('|', [
            $envelope['v'],
            $envelope['alg'],
            $envelope['ts'],
            $envelope['nonce'],
            $envelope['ek'],
            $envelope['iv'],
            $envelope['data'],
        ]);
    }

    /** @param array{v: int, alg: string, direction: string, ts: int, nonce: string, request_nonce: string, iv: string, data: string} $envelope */
    private function responseCanonical(array $envelope): string
    {
        return implode('|', [
            $envelope['v'],
            $envelope['alg'],
            $envelope['direction'],
            $envelope['ts'],
            $envelope['nonce'],
            $envelope['request_nonce'],
            $envelope['iv'],
            $envelope['data'],
        ]);
    }

    private function encryptionKey(string $sessionKey): string
    {
        return hash_hmac('sha256', 'bsphp-v2/enc', $sessionKey, true);
    }

    private function macKey(string $sessionKey): string
    {
        return hash_hmac('sha256', 'bsphp-v2/mac', $sessionKey, true);
    }

    private function aesEncrypt(string $plaintext, string $key, string $iv): string
    {
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (! is_string($encrypted)) {
            throw new PacketCipherException('AES 数据包加密失败。');
        }

        return $encrypted;
    }

    private function aesDecrypt(string $ciphertext, string $key, string $iv): string
    {
        if (strlen($iv) !== 16) {
            throw new PacketCipherException('AES IV 长度错误。');
        }
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if (! is_string($decrypted)) {
            throw new PacketCipherException('AES 数据包解密失败。');
        }

        return $decrypted;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (! is_string($decoded)) {
            throw new PacketCipherException('Base64URL 数据格式错误。');
        }

        return $decoded;
    }
}
