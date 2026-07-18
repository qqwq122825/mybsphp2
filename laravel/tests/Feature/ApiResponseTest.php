<?php

namespace Tests\Feature;

use App\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_response_contains_request_metadata(): void
    {
        $requestId = '01J00000000000000000000000';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/ping');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.service', 'MyBsPHP2')
            ->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_invalid_request_id_is_replaced_with_a_ulid(): void
    {
        $response = $this->withHeader('X-Request-ID', 'invalid request id')
            ->getJson('/api/v2/ping');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertIsString($requestId);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $requestId);
        $response->assertJsonPath('meta.request_id', $requestId);
    }

    public function test_api_not_found_uses_the_unified_error_envelope(): void
    {
        $this->getJson('/api/v2/missing')
            ->assertNotFound()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure([
                'error' => ['code', 'message'],
                'meta' => ['request_id', 'server_time'],
            ]);
    }

    public function test_validation_errors_expose_field_details(): void
    {
        Route::post('/api/v2/test-validation', function (Request $request) {
            $validated = $request->validate([
                'name' => ['required', 'string'],
            ]);

            return ApiResponse::success($validated);
        });

        $this->postJson('/api/v2/test-validation')
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'error' => [
                    'details' => [
                        'fields' => ['name'],
                    ],
                ],
            ]);
    }
}
