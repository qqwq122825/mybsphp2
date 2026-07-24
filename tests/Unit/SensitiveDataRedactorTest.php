<?php

namespace Tests\Unit;

use App\Support\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;

class SensitiveDataRedactorTest extends TestCase
{
    public function test_it_redacts_nested_sensitive_fields_and_message_tokens(): void
    {
        $redactor = new SensitiveDataRedactor;

        $result = $redactor->redact([
            'username' => 'admin',
            'password' => 'plain-password',
            'nested' => [
                'secondary_password' => 'second-secret',
                'access_token' => 'token-value',
                'card_id' => 42,
                'card_key' => 'CARD-SECRET',
            ],
            'message' => 'Authorization: Bearer bearer-secret password=message-secret',
        ]);

        $this->assertSame('admin', $result['username']);
        $this->assertSame(SensitiveDataRedactor::REDACTED, $result['password']);
        $this->assertSame(SensitiveDataRedactor::REDACTED, $result['nested']['secondary_password']);
        $this->assertSame(SensitiveDataRedactor::REDACTED, $result['nested']['access_token']);
        $this->assertSame(42, $result['nested']['card_id']);
        $this->assertSame(SensitiveDataRedactor::REDACTED, $result['nested']['card_key']);
        $this->assertStringNotContainsString('bearer-secret', $result['message']);
        $this->assertStringNotContainsString('message-secret', $result['message']);
    }

    public function test_it_keeps_non_sensitive_business_fields(): void
    {
        $redactor = new SensitiveDataRedactor;

        $this->assertSame([
            'request_id' => '01J00000000000000000000000',
            'application_id' => 9,
            'status' => 'active',
        ], $redactor->redact([
            'request_id' => '01J00000000000000000000000',
            'application_id' => 9,
            'status' => 'active',
        ]));
    }
}
