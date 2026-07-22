<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Models\Application;
use App\Sessions\ClientSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClientSessionController extends Controller
{
    public function handle(Request $request, Application $application, ClientSessionService $sessions): JsonResponse
    {
        $validated = $request->validate(['BSphpSeSsL' => ['nullable', 'string', 'size:64']]);
        return ApiResponse::success(['BSphpSeSsL' => $sessions->issueHandle($validated['BSphpSeSsL'] ?? '')], request: $request);
    }

    public function heartbeat(Request $request, Application $application, ClientSessionService $sessions): JsonResponse
    {
        $validated = $request->validate([
            'BSphpSeSsL' => ['required', 'string', 'size:64'],
            'device_id' => ['nullable', 'string', 'max:160'],
        ]);
        $result = $sessions->heartbeat($application, $validated['BSphpSeSsL'], $validated['device_id'] ?? '');
        return ApiResponse::success($result, request: $request);
    }
}
