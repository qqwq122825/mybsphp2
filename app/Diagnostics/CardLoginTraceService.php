<?php

namespace App\Diagnostics;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardCredentialHasher;
use App\Models\Application;
use App\Models\Card;
use App\Models\ClientSecuritySubjectState;
use App\Models\SystemSetting;
use App\Support\SensitiveDataRedactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class CardLoginTraceService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CardCredentialHasher $cardHasher,
        private readonly SensitiveDataRedactor $redactor,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array{credential: string, secret: string, device: string, safe_code: string}  $parameterNames
     */
    public function recordLegacy(
        Application $application,
        Request $request,
        array $parameters,
        array $parameterNames,
        string $credential,
        string $device,
        string $sessionHandle,
        string $requestNonce,
        bool $compactWire,
        string $legacyResponse,
        bool $universal = false,
    ): void {
        $fields = explode('|', $legacyResponse);
        $returnedDevice = (string) ($fields[8] ?? '');
        $sanitizedFields = $fields;
        foreach ([2 => '[PAYLOAD]', 3 => '[REQUEST_SAFE_CODE]', 9 => '[SESSION_HANDLE]', 10 => '[REPORT_TOKEN]'] as $index => $replacement) {
            if (array_key_exists($index, $sanitizedFields)) {
                $sanitizedFields[$index] = $replacement;
            }
        }

        $this->record(
            $application,
            $request,
            $parameters,
            $parameterNames,
            $credential,
            $device,
            $sessionHandle,
            $requestNonce,
            $compactWire,
            [
                'http_status' => 200,
                'business_code' => (string) ($fields[0] ?? ''),
                'message' => '卡密登录响应已生成',
                'legacy_response' => implode('|', $sanitizedFields),
                'legacy_fields' => $sanitizedFields,
                'returned_device' => $returnedDevice,
                'returned_device_length' => mb_strlen($returnedDevice),
                'request_device_matches_response' => $returnedDevice !== '' && hash_equals($returnedDevice, trim($device)),
            ],
            true,
            $universal,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array{credential: string, secret: string, device: string, safe_code: string}  $parameterNames
     */
    public function recordJson(
        Application $application,
        Request $request,
        array $parameters,
        array $parameterNames,
        string $credential,
        string $device,
        string $sessionHandle,
        string $requestNonce,
        bool $compactWire,
        JsonResponse $response,
        ?string $internalCode = null,
        bool $universal = false,
    ): void {
        $body = $response->getData(true);
        $body = is_array($body) ? $body : [];
        $businessCode = $internalCode ?: (string) data_get($body, 'error.code', 'UNKNOWN');
        $message = (string) data_get($body, 'error.message', '请求未完成');

        $this->record(
            $application,
            $request,
            $parameters,
            $parameterNames,
            $credential,
            $device,
            $sessionHandle,
            $requestNonce,
            $compactWire,
            [
                'http_status' => $response->getStatusCode(),
                'business_code' => $businessCode,
                'message' => $message,
                'json_response' => $this->redactor->redact($body),
            ],
            false,
            $universal,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array{credential: string, secret: string, device: string, safe_code: string}  $parameterNames
     * @param  array<string, mixed>  $responseData
     */
    private function record(
        Application $application,
        Request $request,
        array $parameters,
        array $parameterNames,
        string $credential,
        string $device,
        string $sessionHandle,
        string $requestNonce,
        bool $compactWire,
        array $responseData,
        bool $success,
        bool $universal = false,
    ): void {
        if (! $this->shouldRecord($success, $universal)) {
            return;
        }

        try {
            $card = $this->cardSnapshot($application, $credential);
            $deviceSecurityState = $this->deviceSecuritySnapshot($application, $device);
            $boundDevice = (string) ($card['bound_device'] ?? '');
            $sanitizedParameters = $this->sanitizeParameters($parameters, $parameterNames);
            $responseData['bound_device'] = $boundDevice;
            $responseData['bound_device_length'] = mb_strlen($boundDevice);
            $responseData['request_device_matches_binding'] = $boundDevice === ''
                ? null
                : hash_equals($boundDevice, trim($device));
            $responseData['card_state'] = $card;
            $responseData['device_security_state'] = $deviceSecurityState;
            if (($deviceSecurityState['actively_blocked'] ?? false) === true) {
                $responseData['diagnostic_code'] = 'DEVICE_BLOCKLIST_HONEYPOT';
                $responseData['diagnosis'] = '设备已在蜜罐黑名单；新卡密也会收到隐藏真实数据的返回。';
            }

            $this->audit->record(new AuditEvent(
                action: 'client.card_login_trace',
                targetType: 'application',
                targetId: $application->public_id,
                applicationId: $application->getKey(),
                before: [
                    'application' => ['code' => $application->code, 'name' => $application->name],
                    'api' => 'login.ic',
                    'wire_format' => $compactWire ? 'v3-compact' : 'v2-object',
                    'credential' => $credential,
                    'credential_fingerprint' => $this->fingerprint($credential),
                    'secret_present' => $this->parameter($parameters, $parameterNames['secret']) !== '',
                    'device' => trim($device),
                    'device_length' => mb_strlen(trim($device)),
                    'device_fingerprint' => $this->fingerprint(trim($device)),
                    'session_fingerprint' => $this->fingerprint($sessionHandle),
                    'request_nonce_fingerprint' => $this->fingerprint($requestNonce),
                    'parameters' => $sanitizedParameters,
                    'request_query' => http_build_query($sanitizedParameters, '', '&', PHP_QUERY_RFC3986),
                ],
                after: $responseData,
                reason: (string) ($responseData['business_code'] ?? '').' · '.(string) ($responseData['message'] ?? ''),
                result: $success ? 'success' : 'failed',
                actorRole: 'client',
            ), $request);

            if ($success && ! $universal && $this->currentMode() === 'all') {
                $this->audit->record(new AuditEvent(
                    action: 'client.card_login_success',
                    targetType: 'application',
                    targetId: $application->public_id,
                    applicationId: $application->getKey(),
                    before: [
                        'application' => [
                            'public_id' => $application->public_id,
                            'name' => $application->name,
                        ],
                        'credential' => $credential,
                        'device' => trim($device),
                    ],
                    after: [
                        'internal_code' => 'LOGIN_OK',
                        'internal_message' => '卡密登录成功',
                        'public_code' => 'OK',
                        'public_message' => '登录成功',
                        'http_status' => 200,
                    ],
                    reason: 'LOGIN_OK',
                    result: 'success',
                    actorRole: 'client',
                ), $request);
            }
        } catch (Throwable) {
            // 诊断日志始终旁路业务；日志存储异常不改变客户端登录结果。
        }
    }

    private function shouldRecord(bool $success, bool $universal = false): bool
    {
        return match ($this->currentMode()) {
            'all' => true,
            'errors' => ! $success || $universal,
            default => false,
        };
    }

    private function currentMode(): string
    {
        try {
            $storedMode = (string) SystemSetting::query()
                ->whereKey('card_login_trace_enabled')
                ->value('value');

            return match ($storedMode) {
                'all' => 'all',
                '1', 'errors' => 'errors',
                default => 'simple',
            };
        } catch (Throwable) {
            return 'simple';
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array{credential: string, secret: string, device: string, safe_code: string}  $parameterNames
     * @return array<string, mixed>
     */
    private function sanitizeParameters(array $parameters, array $parameterNames): array
    {
        $result = [];
        foreach ($parameters as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $scalar = is_scalar($value) ? (string) $value : '';
            if ($key === $parameterNames['credential']) {
                $result[$key] = trim($scalar);
            } elseif ($key === $parameterNames['device']) {
                $result[$key] = trim($scalar);
            } elseif ($key === $parameterNames['secret']) {
                $result[$key] = $scalar === '' ? '' : '[CREDENTIAL_SECRET]';
            } elseif ($key === $parameterNames['safe_code']) {
                $result[$key] = $scalar === '' ? '' : '[REQUEST_SAFE_CODE]';
            } elseif (in_array(strtolower($key), ['bsphpsessl', 'session_token'], true)) {
                $result[$key] = $scalar === '' ? '' : '[SESSION_HANDLE]';
            } elseif (in_array($key, ['render_profile', 'launch_telemetry', 'ih'], true)) {
                $result[$key] = $scalar === '' ? '' : '[PRESENT '.strlen($scalar).' BYTES]';
            } else {
                $result[$key] = $this->redactor->redact(mb_substr($scalar, 0, 512), $key);
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function cardSnapshot(Application $application, string $credential): array
    {
        if ($credential === '') {
            return [];
        }
        $card = $application->cards()
            ->where('code_lookup_hash', $this->cardHasher->lookupHash($credential))
            ->first(['public_id', 'status', 'user_status', 'bound_device_ciphertext', 'bound_device_mask']);
        if (! $card instanceof Card) {
            return [];
        }

        return [
            'public_id' => $card->public_id,
            'status' => $card->status,
            'user_status' => $card->user_status,
            'bound_device' => trim((string) $card->bound_device_ciphertext),
            'bound_device_mask' => $card->bound_device_mask,
        ];
    }

    /** @return array<string, mixed> */
    private function deviceSecuritySnapshot(Application $application, string $device): array
    {
        $normalized = trim($device);
        if ($normalized === '') {
            return [];
        }

        $state = ClientSecuritySubjectState::query()
            ->where('application_id', $application->getKey())
            ->where('device_hash', hash('sha256', $normalized))
            ->first();
        if (! $state instanceof ClientSecuritySubjectState) {
            return [];
        }

        $activelyBlocked = $state->status === 'blocked'
            && ($state->blocked_until === null || $state->blocked_until->isFuture());

        return [
            'status' => $state->status,
            'actively_blocked' => $activelyBlocked,
            'risk_score' => $state->risk_score,
            'reason_code' => $state->reason_code,
            'blocked_until' => $state->blocked_until?->toIso8601String(),
            'last_event_at' => $state->last_event_at?->toIso8601String(),
        ];
    }

    private function parameter(array $parameters, string $name): string
    {
        $value = $parameters[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function fingerprint(string $value): string
    {
        return $value === '' ? '' : substr(hash('sha256', $value), 0, 16);
    }
}
