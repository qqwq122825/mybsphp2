<?php

namespace App\Http\Controllers;

use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Cards\CardCredentialHasher;
use App\Http\ApiResponse;
use App\Models\Card;
use App\Risk\IpRiskControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CardActivationController extends Controller
{
    public function activate(Request $request, CardActivationService $activation, CardCredentialHasher $hasher, IpRiskControlService $risk): JsonResponse
    {
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
            $result = $activation->activate(
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

            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
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
