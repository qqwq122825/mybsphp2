<?php

namespace Tests\Unit;

use App\Install\EnsureEnvironmentFile;
use Tests\TestCase;

class EnsureEnvironmentFileTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/ensure-env-'.bin2hex(random_bytes(4));
        mkdir($this->directory, 0755, true);
        file_put_contents($this->directory.'/.env.example', implode(PHP_EOL, [
            'APP_NAME=MyBsPHP',
            'APP_KEY=',
            'APP_URL=http://127.0.0.1:8080',
            '',
        ]));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/{*,.*}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->directory);
        parent::tearDown();
    }

    public function test_it_creates_env_from_example_and_generates_app_key(): void
    {
        EnsureEnvironmentFile::bootstrap($this->directory);

        $envPath = $this->directory.'/.env';
        $this->assertFileExists($envPath);
        $contents = (string) file_get_contents($envPath);
        $this->assertTrue(EnsureEnvironmentFile::hasValidAppKey($contents));
        $this->assertMatchesRegularExpression('/^APP_KEY="base64:[^"]+"$/m', $contents);
    }

    public function test_it_repairs_invalid_hex_app_key_without_clobbering_other_values(): void
    {
        file_put_contents($this->directory.'/.env', implode(PHP_EOL, [
            'APP_NAME="KeepMe"',
            'APP_KEY="6f4a4e3757784ddc4cc15021cac51d33ae5b0ab741daf962afea9d37980356b6"',
            'APP_URL="https://yz.jk92.cc"',
            '',
        ]));

        EnsureEnvironmentFile::bootstrap($this->directory);

        $contents = (string) file_get_contents($this->directory.'/.env');
        $this->assertStringContainsString('APP_NAME="KeepMe"', $contents);
        $this->assertStringContainsString('APP_URL="https://yz.jk92.cc"', $contents);
        $this->assertTrue(EnsureEnvironmentFile::hasValidAppKey($contents));
        $this->assertDoesNotMatchRegularExpression('/APP_KEY="6f4a4e3757784ddc/', $contents);
    }

    public function test_it_leaves_valid_app_key_untouched(): void
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        file_put_contents($this->directory.'/.env', "APP_KEY=\"{$key}\"\nAPP_NAME=Demo\n");

        EnsureEnvironmentFile::bootstrap($this->directory);

        $contents = (string) file_get_contents($this->directory.'/.env');
        $this->assertStringContainsString('APP_KEY="'.$key.'"', $contents);
        $this->assertStringContainsString('APP_NAME=Demo', $contents);
    }
}
