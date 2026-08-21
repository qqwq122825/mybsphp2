<?php

namespace App\Http\Controllers;

use App\Accounts\AccountAuthException;
use App\Accounts\AccountAuthService;
use App\Applications\DailyVerificationCodec;
use App\Applications\HybridPacketCipher;
use App\Applications\PacketCipherException;
use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Cards\UniversalCardService;
use App\Diagnostics\CardLoginTraceService;
use App\Http\ApiResponse;
use App\Models\Application;
use App\Risk\IpRiskControlService;
use App\Security\ClientAuthFailureMasker;
use App\Security\ClientLaunchTelemetryService;
use App\Security\ClientSecurityReportService;
use App\Sessions\ClientSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

final class LegacyGatewayController extends Controller
{
    /** 心跳协议 fields[6...] 的固定预留槽位，避免后续扩展移动既有字段。 */
    private const HEARTBEAT_RESERVED_FIELD_COUNT = 4;

    public function __invoke(
        Request $request,
        HybridPacketCipher $cipher,
        DailyVerificationCodec $dailyCodec,
        CardActivationService $activation,
        UniversalCardService $universalCard,
        AccountAuthService $accountAuth,
        ClientSessionService $clientSessions,
        IpRiskControlService $risk,
        ClientLaunchTelemetryService $launchTelemetry,
        ClientSecurityReportService $securityReports,
        CardLoginTraceService $cardLoginTrace,
        ClientAuthFailureMasker $failureMasker,
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
        $traceParameterNames = [
            'credential' => $legacy->credential_param,
            'secret' => $legacy->credential_secret_param,
            'device' => $legacy->device_param,
            'safe_code' => $legacy->appsafecode_param,
        ];
        $traceCardLogin = $application->mode === 'card_login' && $api === 'login.ic';

        $riskDecision = null;
        if (in_array($api, ['login.ic', 'login.lg'], true)) {
            $riskDecision = $risk->evaluate($application, $request, 'legacy.'.$api, $device, $code."\0".$secret, preg_match('/^[A-Za-z0-9_-]{64}$/', $sessionHandle) === 1);
            if ($riskDecision->shouldBlock()) {
                $blockedResponse = $risk->blockedResponse($riskDecision, $request);
                if ($traceCardLogin) {
                    $cardLoginTrace->recordJson(
                        $application, $request, $parameters, $traceParameterNames,
                        $code, $device, $sessionHandle, $opened['request_nonce'], $compactWire,
                        $blockedResponse, 'RISK_BLOCKED',
                    );
                }

                return $blockedResponse;
            }
        }

        try {
            if ($api === 'BSphpSeSsL.in') {
                $result = ['legacy_response' => $clientSessions->issueHandle($sessionHandle)];
            } elseif ($api === 'security.report') {
                $evidenceRaw = $this->firstParameter($parameters, ['evidence']);
                $evidence = json_decode($evidenceRaw, true);
                if (! is_array($evidence)) {
                    $evidence = ['digest' => $evidenceRaw];
                }
                $securityReports->report(
                    $application, $sessionHandle, $device,
                    $this->firstParameter($parameters, ['report_token']),
                    $this->firstParameter($parameters, ['event_id']),
                    $this->firstParameter($parameters, ['event_code']),
                    $evidence, (string) ($request->ip() ?: '0.0.0.0'),
                );
                $result = ['legacy_response' => implode('|', ['01', 'accepted', $appsafecode, now()->utc()->toISOString()])];
            } elseif (in_array($api, ['timeout.ic', 'timeout.lg'], true)) {
                $expected = $application->mode === 'account_recharge' ? 'timeout.lg' : 'timeout.ic';
                if ($api !== $expected) {
                    throw new AccountAuthException('MODE_MISMATCH', '心跳接口后缀与软件模式不一致。', 409);
                }
                $heartbeat = $clientSessions->heartbeat($application, $sessionHandle, $device);
                $heartbeatToken = $heartbeat['code'] === '5031'
                    ? $securityReports->rotateToken($application, $heartbeat['handle'])
                    : '';
                // 固定位置保持向后兼容：旧客户端只读前 4 项；新客户端
                // 使用 fields[4] 旋转上报令牌，fields[5] 执行日级动态校验，
                // fields[6...] 作为空白预留槽，后续扩展不移动既有字段。
                $heartbeatFields = [
                    $heartbeat['code'], $heartbeat['handle'], $appsafecode, $heartbeat['server_time'],
                    $heartbeatToken, $dailyCodec->dailySignature(),
                    ...array_fill(0, self::HEARTBEAT_RESERVED_FIELD_COUNT, ''),
                ];
                $result = ['legacy_response' => implode('|', $heartbeatFields)];
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
                if ($code === '') {
                    throw new CardActivationException('EMPTY_CARD', '卡密为空。', 422);
                }
                if (mb_strlen($code) > 64 || mb_strlen($secret) > 64) {
                    throw new CardActivationException('INVALID_PARAMETERS', '卡密参数格式错误。', 422);
                }
                $result = $universalCard->matches($code)
                    ? $universalCard->login($application, $device, $appsafecode, $sessionHandle, $request)
                    : $activation->activate($code, $secret, $device, $appsafecode, $sessionHandle);
            }
        } catch (InvalidArgumentException $exception) {
            return ApiResponse::error('SECURITY_REPORT_REJECTED', '上报校验失败。', 422, request: $request);
        } catch (CardActivationException|AccountAuthException $exception) {
            if ($riskDecision !== null) {
                $risk->complete($riskDecision, false);
            }
            $failure = $exception instanceof CardActivationException
                ? $failureMasker->cardException($exception, $request, $application, $code, $device)
                : [
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                    'status' => $exception->status === 404 ? 200 : $exception->status,
                    'details' => $exception->status === 404
                        ? array_replace(['business_status' => 404], $exception->details ?? [])
                        : $exception->details,
                ];

            $errorResponse = ApiResponse::error(
                $failure['code'],
                $failure['message'],
                $failure['status'],
                $failure['details'],
                request: $request,
            );
            if ($traceCardLogin && $exception instanceof CardActivationException) {
                $cardLoginTrace->recordJson(
                    $application, $request, $parameters, $traceParameterNames,
                    $code, $device, $sessionHandle, $opened['request_nonce'], $compactWire,
                    $errorResponse, $exception->errorCode,
                );
            }

            return $errorResponse;
        }

        if ($riskDecision !== null) {
            $risk->complete($riskDecision, true);
            $handle = (string) ($result['BSphpSeSsL'] ?? '');
            try {
                $launchBlocked = $launchTelemetry->record(
                    $application,
                    $handle,
                    $device,
                    // render_profile 是协议层伪装字段名；旧键仅用于已安装版本的过渡兼容。
                    $this->firstParameter($parameters, ['render_profile', 'launch_telemetry']),
                    (string) ($request->ip() ?: '0.0.0.0'),
                );
                if ($launchBlocked) {
                    // 对外保持与普通凭据错误一致；真实 T1 处置只在后台安全事件中可见。
                    if ($application->mode === 'account_recharge') {
                        $launchBlockedResponse = ApiResponse::error('INVALID_CREDENTIALS', '账号或密码错误。', 401, request: $request);
                    } else {
                        $failure = $failureMasker->maskedCardFailure(
                            'T1_BLOCKED',
                            '启动遥测命中高置信处置。',
                            $request,
                            $application,
                            $code,
                            $device,
                        );
                        $launchBlockedResponse = ApiResponse::error(
                            $failure['code'],
                            $failure['message'],
                            $failure['status'],
                            request: $request,
                        );
                    }
                    if ($traceCardLogin) {
                        $cardLoginTrace->recordJson(
                            $application, $request, $parameters, $traceParameterNames,
                            $code, $device, $sessionHandle, $opened['request_nonce'], $compactWire,
                            $launchBlockedResponse, 'T1_BLOCKED',
                        );
                    }

                    return $launchBlockedResponse;
                }
            } catch (InvalidArgumentException) {
                // 遥测是可选信号；格式异常不改变已经完成的登录业务结果。
            }
            $reportToken = $securityReports->rotateToken($application, $handle);
            if ($reportToken !== '' && isset($result['legacy_response'])) {
                $parts = explode('|', (string) $result['legacy_response']);
                $parts[10] = $reportToken;
                $result['legacy_response'] = implode('|', $parts);
                $result['report_token'] = $reportToken;
            }
        }

        if ($riskDecision !== null) {
            $result = $risk->applyDecoy($result, $application, $riskDecision);
        }

        if ($traceCardLogin) {
            $cardLoginTrace->recordLegacy(
                $application, $request, $parameters, $traceParameterNames,
                $code, $device, $sessionHandle, $opened['request_nonce'], $compactWire,
                (string) $result['legacy_response'],
            );
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
