<?php

namespace Tests\Unit;

use App\Install\ReinstallationTokenManager;
use Tests\TestCase;

class ReinstallationTokenManagerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/reinstall-tokens');
        config(['mybsphp.reinstall_tokens_directory' => $this->directory]);
        $this->cleanDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanDirectory();
        @rmdir($this->directory);

        parent::tearDown();
    }

    public function test_a_token_is_random_stored_as_a_hash_and_consumed_once(): void
    {
        $tokens = app(ReinstallationTokenManager::class);
        $token = $tokens->issue(30);
        $path = $this->directory.'/'.hash('sha256', $token).'.json';

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertFileExists($path);
        $this->assertStringNotContainsString($token, (string) file_get_contents($path));
        $this->assertTrue($tokens->consume($token));
        $this->assertFalse($tokens->consume($token));
        $this->assertFileDoesNotExist($path);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->assertFalse(app(ReinstallationTokenManager::class)->consume('invalid'));
    }

    private function cleanDirectory(): void
    {
        $paths = glob($this->directory.'/*');

        if (! is_array($paths)) {
            return;
        }

        foreach ($paths as $path) {
            @unlink($path);
        }
    }
}
