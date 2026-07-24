<?php

namespace App\Http\Controllers;

use App\Accounts\AccountAuthException;
use App\Accounts\AccountAuthService;
use App\Http\ApiResponse;
use App\Models\Application;
use App\Risk\IpRiskControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountAuthController extends Controller
{
    public function exists(Request $request, Application $application): JsonResponse
    {
        $username = trim((string) $request->input('username', ''));

        return ApiResponse::success(['exists' => $application->accounts()->where('username', $username)->exists()], request: $request);
    }

    public function register(Request $request, Application $application, AccountAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:32'], 'password' => ['required', 'string', 'max:72'],
            'device_id' => ['nullable', 'string', 'max:160'], 'card_code' => ['nullable', 'string', 'max:64'],
            'card_secret' => ['nullable', 'string', 'max:64'], 'invitation_code' => ['nullable', 'string', 'max:24'],
            'appsafecode' => ['nullable', 'string', 'max:255'],
            'BSphpSeSsL' => ['nullable', 'string', 'size:64'],
        ]);

        return $this->run($request, fn () => $auth->register(
            $application, $validated['username'], $validated['password'], $validated['device_id'] ?? '',
            $validated['card_code'] ?? '', $validated['card_secret'] ?? '', $validated['invitation_code'] ?? '',
            $validated['appsafecode'] ?? '',
            $validated['BSphpSeSsL'] ?? '',
        ), 201);
    }

    public function login(Request $request, Application $application, AccountAuthService $auth, IpRiskControlService $risk): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64'], 'password' => ['required', 'string', 'max:72'],
            'device_id' => ['nullable', 'string', 'max:160'], 'appsafecode' => ['nullable', 'string', 'max:255'],
            'BSphpSeSsL' => ['nullable', 'string', 'size:64'],
        ]);
        $decision = $risk->evaluate($application, $request, 'accounts.login', $validated['device_id'] ?? '');
        if ($decision->shouldBlock()) {
            return $risk->blockedResponse($decision, $request);
        }

        return $this->run($request, fn () => $risk->applyDecoy(
            $auth->login($application, $validated['username'], $validated['password'], $validated['device_id'] ?? '', $validated['appsafecode'] ?? '', $validated['BSphpSeSsL'] ?? ''),
            $application,
            $decision,
        ));
    }

    public function recharge(Request $request, Application $application, AccountAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64'], 'card_code' => ['required', 'string', 'max:64'],
            'card_secret' => ['nullable', 'string', 'max:64'], 'appsafecode' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->run($request, fn () => $auth->recharge($application, $validated['username'], $validated['card_code'], $validated['card_secret'] ?? '', $validated['appsafecode'] ?? ''));
    }

    /** @param callable(): array<string, mixed> $callback */
    private function run(Request $request, callable $callback, int $status = 200): JsonResponse
    {
        try {
            return ApiResponse::success($callback(), status: $status, request: $request);
        } catch (AccountAuthException $exception) {
            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                $exception->details,
                request: $request,
            );
        }
    }
}
