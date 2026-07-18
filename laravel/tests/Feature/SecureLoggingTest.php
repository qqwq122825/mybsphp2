<?php

namespace Tests\Feature;

use App\Logging\ConfigureSecureLogging;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecureLoggingTest extends TestCase
{
    public function test_log_channel_redacts_context_and_message_secrets(): void
    {
        $path = storage_path('logs/secure-logging-test.log');
        @unlink($path);

        config()->set('logging.channels.secure_logging_test', [
            'driver' => 'single',
            'path' => $path,
            'tap' => [ConfigureSecureLogging::class],
            'replace_placeholders' => true,
        ]);
        Log::forgetChannel('secure_logging_test');
        $logger = Log::channel('secure_logging_test');

        $logger->info(
            'login password=message-secret Authorization: Bearer bearer-secret',
            [
                'username' => 'admin',
                'password' => 'context-secret',
                'nested' => ['card_key' => 'CARD-SECRET'],
            ],
        );

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('admin', $contents);
        $this->assertStringContainsString('[REDACTED]', $contents);
        $this->assertStringNotContainsString('message-secret', $contents);
        $this->assertStringNotContainsString('bearer-secret', $contents);
        $this->assertStringNotContainsString('context-secret', $contents);
        $this->assertStringNotContainsString('CARD-SECRET', $contents);

        Log::forgetChannel('secure_logging_test');
        @unlink($path);
    }

    public function test_http_completion_log_contains_the_response_request_id(): void
    {
        $log = Log::spy();
        $requestId = '01J00000000000000000000003';

        $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/ping')
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId);

        $log->shouldHaveReceived('info', [
            'http.request.completed',
            \Mockery::on(
                fn (array $context): bool => $context['request_id'] === $requestId
                    && $context['status'] === 200
                    && is_int($context['duration_ms']),
            ),
        ]);
    }
}
