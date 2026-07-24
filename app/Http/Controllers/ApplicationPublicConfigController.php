<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApplicationPublicConfigController extends Controller
{
    public function __invoke(Request $request, Application $application): JsonResponse
    {
        return ApiResponse::success([
            'application' => [
                'id' => $application->public_id,
                'code' => $application->code,
                'name' => $application->name,
                'mode' => $application->mode,
                'status' => $application->status,
                'is_open' => $application->status === 'active',
                'closed_message' => $application->closedMessage(),
                'version' => $application->version_name,
                'announcement' => $application->announcement,
                'binding_mode' => 'verified',
                'max_concurrent_sessions' => $application->max_concurrent_sessions,
            ],
        ], request: $request);
    }
}
