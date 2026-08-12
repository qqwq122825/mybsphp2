<?php

namespace App\Security;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Cards\CardActivationException;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ClientAuthFailureMasker
{
    public function __construct(
        private readonly AuditLogger $audit,
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
     * @return array{code: string, message: string, status: int, details: array<string, mixed>|null, masked: bool, reference: int|null}
     */
    public function cardException(CardActivationException $exception, Request $request, ?Application $application = null): array
    {
        if (in_array($exception->errorCode, self::CARD_INPUT_ERRORS, true)) {
            return [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'status' => $exception->status,
                'details' => null,
                'masked' => false,
                'reference' => null,
            ];
        }

        return $this->maskedCardFailure(
            $exception->errorCode,
            $exception->getMessage(),
            $request,
            $application,
        );
    }

    /**
     * @return array{code: string, message: string, status: int, details: null, masked: true, reference: int}
     */
    public function maskedCardFailure(
        string $internalCode,
        string $internalMessage,
        Request $request,
        ?Application $application = null,
    ): array {
        $reference = random_int(201, 999);

        Log::notice('client.card_auth_failure_masked', [
            'diagnostic_reference' => $reference,
            'internal_code' => $internalCode,
            'internal_message' => $internalMessage,
            'application_public_id' => $application?->public_id,
            'request_path' => $request->path(),
            'ip_hash' => hash('sha256', (string) ($request->ip() ?: '0.0.0.0')),
        ]);

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
                ],
                after: [
                    'diagnostic_reference' => $reference,
                    'diagnostic_code' => $internalCode,
                    'internal_code' => $internalCode,
                    'internal_message' => $internalMessage,
                    'public_code' => 'INVALID_CARD',
                    'public_message' => '卡密不存在'.$reference,
                    'http_status' => 200,
                ],
                reason: "诊断编号 {$reference} · {$internalCode}",
                result: 'failed',
                actorRole: 'client',
            ), $request);
        } catch (\Throwable) {
            // 审计存储旁路认证响应；文件日志仍保留同一诊断编号。
        }

        return [
            'code' => 'INVALID_CARD',
            'message' => '卡密不存在'.$reference,
            'status' => 200,
            'details' => null,
            'masked' => true,
            'reference' => $reference,
        ];
    }
}
