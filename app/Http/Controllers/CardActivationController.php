<?php

namespace App\Http\Controllers;

use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Cards\CardCredentialHasher;
use App\Http\ApiResponse;
use App\Models\Card;
use App\Risk\IpRiskControlService;
use App\Security\ClientAuthFailureMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CardActivationController extends Controller
{
    public function activate(
        Request $request,
        CardActivationService $activation,
        CardCredentialHasher $hasher,
        IpRiskControlService $risk,
        ClientAuthFailureMasker $failureMasker,
    ): JsonResponse {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:8', 'max:64'],
            'secret' => ['nullable', 'string', 'max:64'],
            'device_id' => ['nullable', 'string', 'max:160'],
            'appsafecode' => ['nullable', 'string', 'max:255'],
            'BSphpSeSsL' => ['nullable', 'string', 'size:64'],
        ]);

        $card = Card::query()->with('application')->where('code_lookup_hash', $hasher->lookupHash($validated['code']))->first();
        $decision = $card instanceof Card
            ? $risk->evaluate($card->application, $request, 'cards.login', $validated['device_id'] ?? '', $validated['code']."\0".($validated['secret'] ?? ''), isset($validated['BSphpSeSsL']))
            : null;
        if ($decision?->shouldBlock()) {
            return $risk->blockedResponse($decision, $request);
        }

        try {
            if (! $card instanceof Card) {
                throw new CardActivationException('INVALID_CARD', '卡密不存在。', 404);
            }
            $result = $activation->activate(
                $card->application,
                $validated['code'],
                $validated['secret'] ?? '',
                $validated['device_id'] ?? '',
                $validated['appsafecode'] ?? '',
                $validated['BSphpSeSsL'] ?? '',
            );
        } catch (CardActivationException $exception) {
            if ($decision !== null) {
                $risk->complete($decision, false);
            }
            $failure = $failureMasker->cardException(
                $exception,
                $request,
                $card?->application,
                $validated['code'],
                $validated['device_id'] ?? '',
            );

            return ApiResponse::error(
                $failure['code'],
                $failure['message'],
                $failure['status'],
                $failure['details'],
                request: $request,
            );
        }

        if ($decision !== null) {
            $risk->complete($decision, true);
            $result = $risk->applyDecoy($result, $card->application, $decision);
        }

        return ApiResponse::success($result, request: $request);
    }
}
