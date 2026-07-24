<?php

namespace App\Http\Controllers;

use App\Accounts\AccountAuthException;
use App\Accounts\AccountAuthService;
use App\Applications\HybridPacketCipher;
use App\Applications\PacketCipherException;
use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Http\ApiResponse;
use App\Models\Application;
use App\Risk\IpRiskControlService;
use App\Sessions\ClientSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class LegacyGatewayController extends Controller
{
    public function __invoke(
        Request $request,
        HybridPacketCipher $cipher,
        CardActivationService $activation,
        AccountAuthService $accountAuth,
        ClientSessionService $clientSessions,
        IpRiskControlService $risk,
    ): JsonResponse {
        $appCode = (string) $request->query('appid', '');
        $gatewayToken = (string) $request->query('md5', '');
        if (! preg_match('/^[0-9]{1,20}$/', $appCode) || ! preg_match('/^[a-f0-9]{32}$/', $gatewayToken)) {
            return ApiResponse::error('INVALID_GATEWAY', '服务器地址参数错误。', 404, request: $request);
        }

        $application = Application::query()
            ->with('legacyConfig')
            ->where('code', $appCode)
            ->first();
        if (
            ! $application instanceof Application
            || ! is_string($application->gateway_token)
            || ! hash_equals($application->gateway_token, $gatewayToken)
        ) {
            return ApiResponse::error('INVALID_GATEWAY', '服务器地址参数错误。', 404, request: $request);
        }
        if ($application->status !== 'active') {
            return ApiResponse::error('APP_DISABLED', $application->closedMessage(), 403, [
                'application_status' => $application->status,
                'is_open' => false,
            ], request: $request);
        }
        if ($application->encryption_mode !== 'hybrid_rsa_aes_hmac') {
            return ApiResponse::error('ENCRYPTION_MODE_MISMATCH', '当前软件使用旧版数据包模式。', 409, request: $request);
        }

        $wirePacket = $request->json()->all();
        $compactWire = array_is_list($wirePacket);
        $envelope = $compactWire ? $this->expandCompactRequest($wirePacket) : $wirePacket;
        if ($envelope === null) {
            return ApiResponse::error('INVALID_PACKET', '数据包结构错误。', 400, request: $request);
        }
        $legacy = $application->legacyConfig()->firstOrCreate([], [
            'api_param' => 'api',
            'device_param' => 'key',
            'appsafecode_param' => 'appsafecode',
            ...$application->legacyLoginDefaults(),
            'max_clock_skew_seconds' => 300,
            'legacy_enabled' => true,
        ]);
        if (! $legacy->legacy_enabled) {
            return ApiResponse::error('APPEN_DISABLED', '该软件未开放 AppEn 接口。', 403, request: $request);
        }

        try {
            $opened = $cipher->openRequest(
                $application,
                $envelope,
                $legacy->max_clock_skew_seconds,
            );
        } catch (PacketCipherException $exception) {
            return ApiResponse::error('INVALID_PACKET', $exception->getMessage(), 400, request: $request);
        }

        $replayKey = 'appen:nonce:'.$application->getKey().':'.hash('sha256', $opened['request_nonce']);
        if (! Cache::add($replayKey, true, now()->addMinutes(10))) {
            return ApiResponse::error('PACKET_REPLAYED', '数据包 nonce 已使用。', 409, request: $request);
        }

        parse_str($opened['plaintext'], $parameters);
        $api = $this->parameter($parameters, $legacy->api_param);
        $code = $this->parameter($parameters, $legacy->credential_param);
        $secret = $this->parameter($parameters, $legacy->credential_secret_param);
        $device = $this->parameter($parameters, $legacy->device_param);
        $appsafecode = $this->parameter($parameters, $legacy->appsafecode_param);
        $sessionHandle = $this->firstParameter($parameters, ['BSphpSeSsL', 'bsphpsessl', 'session_token']);

        $riskDecision = null;
        if (in_array($api, ['login.ic', 'login.lg'], true)) {
            $riskDecision = $risk->evaluate($application, $request, 'legacy.'.$api, $device);
            if ($riskDecision->shouldBlock()) {
                return $risk->blockedResponse($riskDecision, $request);
            }
        }

        try {
            if ($api === 'BSphpSeSsL.in') {
                $result = ['legacy_response' => $clientSessions->issueHandle($sessionHandle)];
            } elseif (in_array($api, ['timeout.ic', 'timeout.lg'], true)) {
                $expected = $application->mode === 'account_recharge' ? 'timeout.lg' : 'timeout.ic';
                if ($api !== $expected) {
                    throw new AccountAuthException('MODE_MISMATCH', '心跳接口后缀与软件模式不一致。', 409);
                }
                $heartbeat = $clientSessions->heartbeat($application, $sessionHandle, $device);
                $result = ['legacy_response' => implode('|', [$heartbeat['code'], $heartbeat['handle'], $appsafecode, $heartbeat['server_time']])];
            } elseif ($application->mode === 'account_recharge') {
                $result = match ($api) {
                    'login.lg' => $accountAuth->login($application, $code, $secret, $device, $appsafecode, $sessionHandle),
                    'registration.lg', 'registrationaska.lg' => $accountAuth->register(
                        $application, $code, $secret, $device,
                        $this->firstParameter($parameters, ['icid', 'ka', 'card', 'card_code']),
                        $this->firstParameter($parameters, ['icpwd', 'mi', 'card_password', 'card_secret']),
                        $this->firstParameter($parameters, ['invite', 'invitation', 'invitation_code', 'u']),
                        $appsafecode,
                        $sessionHandle,
                    ),
                    'chong.lg' => $accountAuth->recharge(
                        $application, $code,
                        $this->firstParameter($parameters, ['icid', 'ka', 'card', 'card_code']),
                        $this->firstParameter($parameters, ['icpwd', 'mi', 'card_password', 'card_secret']),
                        $appsafecode,
                    ),
                    'GetPleaseregister.lg' => [
                        'legacy_response' => $application->accounts()->where('username', $code)->exists() ? '00|账号已存在' : '01|账号可注册',
                    ],
                    default => throw new AccountAuthException('UNKNOWN_API', '接口名称不存在。', 404),
                };
            } else {
                if ($api !== 'login.ic') {
                    throw new CardActivationException('UNKNOWN_API', '接口名称不存在。', 404);
                }
                if ($code === '' || mb_strlen($code) > 64 || mb_strlen($secret) > 64) {
                    throw new CardActivationException('INVALID_PARAMETERS', '卡密参数格式错误。', 422);
                }
                $result = $activation->activate($code, $secret, $device, $appsafecode, $sessionHandle);
            }
        } catch (CardActivationException|AccountAuthException $exception) {
            // Some Nginx installations internally rewrite upstream 404 responses
            // to a GET error page, which turns the useful JSON into HTTP_405.
            // Keep legacy business errors on a successful transport response.
            $transportStatus = $exception->status === 404 ? 200 : $exception->status;
            $details = $exception instanceof AccountAuthException ? $exception->details : null;
            if ($exception->status === 404) {
                $details = array_replace(['business_status' => 404], $details ?? []);
            }

            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $transportStatus,
                $details,
                request: $request,
            );
        }

        if ($riskDecision !== null) {
            $result = $risk->applyDecoy($result, $application, $riskDecision);
        }

        $responseEnvelope = $cipher->sealResponse(
            (string) $result['legacy_response'],
            $opened['session_key'],
            $opened['request_nonce'],
        );

        return response()->json($compactWire
            ? $this->compactResponse($responseEnvelope)
            : $responseEnvelope);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function parameter(array $parameters, string $name): string
    {
        $value = $parameters[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** @param array<string, mixed> $parameters @param list<string> $names */
    private function firstParameter(array $parameters, array $names): string
    {
        foreach ($names as $name) {
            $value = $this->parameter($parameters, $name);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Wire v3 request: [3, ts, nonce, wrappedKey, iv, ciphertext, mac, auth].
     * The cryptographic canonical form remains protocol v2 internally.
     *
     * @param  array<int, mixed>  $packet
     * @return array<string, mixed>|null
     */
    private function expandCompactRequest(array $packet): ?array
    {
        if (count($packet) !== 8 || ($packet[0] ?? null) !== 3) {
            return null;
        }

        return [
            'v' => 2,
            'alg' => HybridPacketCipher::ALGORITHM,
            'ts' => $packet[1],
            'nonce' => $packet[2],
            'ek' => $packet[3],
            'iv' => $packet[4],
            'data' => $packet[5],
            'mac' => $packet[6],
            'auth' => $packet[7],
        ];
    }

    /**
     * Wire v3 response: [3, ts, responseNonce, requestNonce, iv, ciphertext, mac].
     * Error responses remain ordinary JSON objects so clients can display the message.
     *
     * @param  array<string, mixed>  $envelope
     * @return list<mixed>
     */
    private function compactResponse(array $envelope): array
    {
        return [
            3,
            $envelope['ts'],
            $envelope['nonce'],
            $envelope['request_nonce'],
            $envelope['iv'],
            $envelope['data'],
            $envelope['mac'],
        ];
    }
}
