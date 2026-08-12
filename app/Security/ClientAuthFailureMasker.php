<?php

namespace App\Security;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardActivationException;
use App\Cards\CardCredentialHasher;
use App\Models\Application;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ClientAuthFailureMasker
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CardCredentialHasher $cardHasher,
    ) {}

    /** @var list<string> */
    private const CARD_INPUT_ERRORS = [
        'EMPTY_CARD',
        'INVALID_PARAMETERS',
        'DEVICE_REQUIRED',
        'UNKNOWN_API',
        'APP_DISABLED',
    ];

    /**
     * @return array{code: string, message: string, status: int, details: array<string, mixed>|null, masked: bool}
     */
    public function cardException(
        CardActivationException $exception,
        Request $request,
        ?Application $application = null,
        string $credential = '',
        string $device = '',
    ): array {
        if (in_array($exception->errorCode, self::CARD_INPUT_ERRORS, true)) {
            return [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'status' => $exception->status,
                'details' => null,
                'masked' => false,
            ];
        }

        return $this->maskedCardFailure(
            $exception->errorCode,
            $exception->getMessage(),
            $request,
            $application,
            $credential,
            $device,
        );
    }

    /**
     * @return array{code: string, message: string, status: int, details: null, masked: true}
     */
    public function maskedCardFailure(
        string $internalCode,
        string $internalMessage,
        Request $request,
        ?Application $application = null,
        string $credential = '',
        string $device = '',
    ): array {
        $credentialContext = $this->credentialContext($application, $credential);

        Log::notice('client.card_auth_failure_masked', [
            'internal_code' => $internalCode,
            'internal_message' => $internalMessage,
            'application_public_id' => $application?->public_id,
            'request_path' => $request->path(),
            'ip_hash' => hash('sha256', (string) ($request->ip() ?: '0.0.0.0')),
        ]);

        if (! in_array($internalCode, ['INVALID_CARD', 'CARD_EXPIRED'], true)) {
            try {
                $this->audit->record(new AuditEvent(
                    action: 'client.auth_failure_masked',
                    targetType: 'application',
                    targetId: $application?->public_id,
                    applicationId: $application?->getKey(),
                    before: [
                        'application' => [
                            'public_id' => $application?->public_id,
                            'name' => $application?->name,
                        ],
                        ...$credentialContext,
                        'device' => trim($device),
                    ],
                    after: [
                        'diagnostic_code' => $internalCode,
                        'internal_code' => $internalCode,
                        'internal_message' => $internalMessage,
                        'public_code' => 'INVALID_CARD',
                        'public_message' => '卡密不存在',
                        'http_status' => 200,
                    ],
                    reason: $internalCode,
                    result: 'failed',
                    actorRole: 'client',
                ), $request);
            } catch (\Throwable) {
                // 审计存储旁路认证响应；认证结果仍按统一文案返回。
            }
        }

        return [
            'code' => 'INVALID_CARD',
            'message' => '卡密不存在',
            'status' => 200,
            'details' => null,
            'masked' => true,
        ];
    }

    /** @return array<string, string> */
    private function credentialContext(?Application $application, string $credential): array
    {
        $credential = trim($credential);
        if (! $application instanceof Application || $credential === '') {
            return [];
        }
        $card = $application->cards()
            ->where('code_lookup_hash', $this->cardHasher->lookupHash($credential))
            ->first(['public_id']);
        $length = mb_strlen($credential);

        return [
            'card_public_id' => $card instanceof Card ? $card->public_id : '',
            'credential_masked' => $length <= 4
                ? str_repeat('•', $length)
                : str_repeat('•', min(12, $length - 4)).mb_substr($credential, -4),
            'credential_fingerprint' => substr(hash('sha256', $credential), 0, 16),
        ];
    }
}
