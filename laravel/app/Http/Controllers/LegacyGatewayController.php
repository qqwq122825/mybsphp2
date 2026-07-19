<?php

namespace App\Http\Controllers;

use App\Applications\HybridPacketCipher;
use App\Applications\PacketCipherException;
use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Http\ApiResponse;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class LegacyGatewayController extends Controller
{
    public function __invoke(
        Request $request,
        HybridPacketCipher $cipher,
        CardActivationService $activation,
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
            return ApiResponse::error('APP_DISABLED', '软件当前未开放。', 403, request: $request);
        }
        if ($application->encryption_mode !== 'hybrid_rsa_aes_hmac') {
            return ApiResponse::error('ENCRYPTION_MODE_MISMATCH', '当前软件使用旧版数据包模式。', 409, request: $request);
        }

        $envelope = $request->json()->all();
        $legacy = $application->legacyConfig()->firstOrCreate([], [
            'api_param' => 'api',
            'credential_param' => 'icid',
            'credential_secret_param' => 'icpwd',
            'device_param' => 'key',
            'appsafecode_param' => 'appsafecode',
            'login_api_name' => 'login.ic',
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

        if ($api !== $legacy->login_api_name) {
            return ApiResponse::error('UNKNOWN_API', '接口名称不存在。', 404, request: $request);
        }
        if ($code === '' || $secret === '' || mb_strlen($code) > 64 || mb_strlen($secret) > 64) {
            return ApiResponse::error('INVALID_PARAMETERS', '卡密参数格式错误。', 422, request: $request);
        }

        try {
            $result = $activation->activate($code, $secret, $device, $appsafecode);
        } catch (CardActivationException $exception) {
            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                request: $request,
            );
        }

        return response()->json($cipher->sealResponse(
            (string) $result['legacy_response'],
            $opened['session_key'],
            $opened['request_nonce'],
        ));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function parameter(array $parameters, string $name): string
    {
        $value = $parameters[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
