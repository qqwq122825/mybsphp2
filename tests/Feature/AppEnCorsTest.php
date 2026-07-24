<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppEnCorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_appen_preflight_allows_local_http_fixtures(): void
    {
        $this->call(
            'OPTIONS',
            '/AppEn.php?appid=1&md5=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            [],
            [],
            [],
            [
                'HTTP_ORIGIN' => 'http://127.0.0.1:5500',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,accept',
            ],
        )
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_appen_post_includes_cors_headers_for_browser_clients(): void
    {
        $this->call(
            'POST',
            '/AppEn.php?appid=1&md5=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_ORIGIN' => 'null',
            ],
            '{}',
        )
            ->assertNotFound()
            ->assertJsonPath('ok', false)
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
