<?php

namespace Tests\Unit;

use App\Applications\ApplicationCredentialService;
use App\Applications\HybridPacketCipher;
use App\Applications\PacketCipherException;
use App\Models\Application;
use Tests\TestCase;

class HybridPacketCipherTest extends TestCase
{
    public function test_ios_compatible_hybrid_packet_round_trip(): void
    {
        $application = $this->application();
        $cipher = app(HybridPacketCipher::class);
        $requestPayload = 'api=login.ic&icid=TEST-001&key=IOS-DEVICE';

        $envelope = $cipher->sealRequest($application, $requestPayload);
        $opened = $cipher->openRequest($application, $envelope);

        $this->assertSame(2, $envelope['v']);
        $this->assertSame(HybridPacketCipher::ALGORITHM, $envelope['alg']);
        $this->assertSame($requestPayload, $opened['plaintext']);
        $this->assertSame(32, strlen($opened['session_key']));

        $legacyResponse = '01|xx|0x100000|SAFE|ABCDEF1234|1|2030-01-01T00:00:00Z|1|IOS-DEVICE||';
        $response = $cipher->sealResponse(
            $legacyResponse,
            $opened['session_key'],
            $opened['request_nonce'],
        );

        $this->assertSame(
            $legacyResponse,
            $cipher->openResponse($response, $opened['session_key'], $opened['request_nonce']),
        );
    }

    public function test_modified_packet_is_rejected_before_decryption(): void
    {
        $application = $this->application();
        $cipher = app(HybridPacketCipher::class);
        $envelope = $cipher->sealRequest($application, 'api=login.ic');
        $envelope['data'] .= 'A';

        $this->expectException(PacketCipherException::class);
        $this->expectExceptionMessage('通信认证 Key 校验失败');

        $cipher->openRequest($application, $envelope);
    }

    public function test_expired_packet_is_rejected(): void
    {
        $application = $this->application();
        $cipher = app(HybridPacketCipher::class);
        $envelope = $cipher->sealRequest($application, 'api=login.ic', time() - 601);

        $this->expectException(PacketCipherException::class);
        $this->expectExceptionMessage('超过允许时间窗口');

        $cipher->openRequest($application, $envelope, 300);
    }

    private function application(): Application
    {
        $credentials = app(ApplicationCredentialService::class)->generate();

        return (new Application)->forceFill([
            'code' => '201',
            ...$credentials,
        ]);
    }
}
