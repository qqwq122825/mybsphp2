<?php

namespace App\Http\Controllers;

use App\Cards\CardActivationException;
use App\Cards\CardActivationService;
use App\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CardActivationController extends Controller
{
    public function activate(Request $request, CardActivationService $activation): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:8', 'max:64'],
            'secret' => ['required', 'string', 'min:4', 'max:64'],
            'device_id' => ['nullable', 'string', 'max:160'],
            'appsafecode' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $activation->activate(
                $validated['code'],
                $validated['secret'],
                $validated['device_id'] ?? '',
                $validated['appsafecode'] ?? '',
            );
        } catch (CardActivationException $exception) {
            return ApiResponse::error(
                $exception->errorCode,
                $exception->getMessage(),
                $exception->status,
                request: $request,
            );
        }

        return ApiResponse::success($result, request: $request);
    }
}
