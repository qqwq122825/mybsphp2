<?php

namespace App\Applications;

use App\Models\Application;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class ApplicationCredentialService
{
    /**
     * @return array{
     *     gateway_token: string,
     *     communication_key_ciphertext: string,
     *     encryption_mode: string,
     *     server_public_key: string,
     *     server_private_key_ciphertext: string,
     *     legacy_packet_key_ciphertext: string,
     * }
     */
    public function generate(): array
    {
        $keyPair = $this->generateRsaKeyPair();

        return [
            'gateway_token' => bin2hex(random_bytes(16)),
            'communication_key_ciphertext' => bin2hex(random_bytes(16)),
            'encryption_mode' => 'hybrid_rsa_aes_hmac',
            'server_public_key' => $keyPair['public'],
            'server_private_key_ciphertext' => $keyPair['private'],
            'legacy_packet_key_ciphertext' => Str::random(18),
        ];
    }

    public function ensure(Application $application): Application
    {
        if (
            is_string($application->gateway_token)
            && $application->gateway_token !== ''
            && $application->communicationKey() !== ''
            && is_string($application->server_public_key)
            && $application->server_public_key !== ''
            && $application->serverPrivateKey() !== ''
            && $application->legacyPacketKey() !== ''
        ) {
            return $application;
        }

        $generated = $this->generate();
        $communicationKey = $application->communicationKey() ?: $generated['communication_key_ciphertext'];

        $application->forceFill([
            'gateway_token' => $application->gateway_token ?: $generated['gateway_token'],
            'communication_key_ciphertext' => $communicationKey,
            'encryption_mode' => $application->encryption_mode ?: $generated['encryption_mode'],
            'server_public_key' => $application->server_public_key ?: $generated['server_public_key'],
            'server_private_key_ciphertext' => $application->serverPrivateKey() ?: $generated['server_private_key_ciphertext'],
            'legacy_packet_key_ciphertext' => $application->legacyPacketKey() ?: $generated['legacy_packet_key_ciphertext'],
            'client_secret_hash' => Hash::make($communicationKey),
        ])->save();

        return $application->refresh();
    }

    public function serverAddress(Application $application): string
    {
        return rtrim((string) config('app.url'), '/')
            .'/AppEn.php?appid='.$application->code
            .'&md5='.$application->gateway_token;
    }

    public function objectiveCMacros(Application $application): string
    {
        $publicKey = str_replace(
            ['\\', "\r", "\n"],
            ['\\\\', '', '\\n'],
            (string) $application->server_public_key,
        );

        return '#define BSPHP_HOST @"'.$this->serverAddress($application).'"'.PHP_EOL
            .'#define BSPHP_MUTUALKEY @"'.$application->communicationKey().'"'.PHP_EOL
            .'#define BSPHP_SERVER_PUBLIC_KEY @"'.$publicKey.'"';
    }

    /**
     * @return array{public: string, private: string}
     */
    private function generateRsaKeyPair(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new RuntimeException('生成服务器 RSA 证书失败。');
        }

        $privateKey = '';
        if (! openssl_pkey_export($key, $privateKey)) {
            throw new RuntimeException('导出服务器 RSA 私钥失败。');
        }

        $details = openssl_pkey_get_details($key);
        if (
            ! is_array($details)
            || ! is_array($details['rsa'] ?? null)
            || ! is_string($details['rsa']['n'] ?? null)
            || ! is_string($details['rsa']['e'] ?? null)
        ) {
            throw new RuntimeException('导出服务器 RSA 公钥失败。');
        }

        $publicDer = $this->derSequence(
            $this->derInteger($details['rsa']['n']).$this->derInteger($details['rsa']['e']),
        );
        $publicKey = "-----BEGIN RSA PUBLIC KEY-----\n"
            .chunk_split(base64_encode($publicDer), 64, "\n")
            ."-----END RSA PUBLIC KEY-----\n";

        return [
            'public' => $publicKey,
            'private' => $privateKey,
        ];
    }

    private function derSequence(string $contents): string
    {
        return "\x30".$this->derLength(strlen($contents)).$contents;
    }

    private function derInteger(string $integer): string
    {
        $integer = ltrim($integer, "\x00");
        if ($integer === '' || (ord($integer[0]) & 0x80) !== 0) {
            $integer = "\x00".$integer;
        }

        return "\x02".$this->derLength(strlen($integer)).$integer;
    }

    private function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $encoded = '';
        while ($length > 0) {
            $encoded = chr($length & 0xFF).$encoded;
            $length >>= 8;
        }

        return chr(0x80 | strlen($encoded)).$encoded;
    }
}
