<?php

namespace Tests\Unit;

use App\Install\EnvironmentFileWriter;
use Tests\TestCase;

class EnvironmentFileWriterTest extends TestCase
{
    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environmentPath = storage_path('framework/testing/install.env');
        file_put_contents($this->environmentPath, "APP_NAME=\"Old\"\nAPP_INSTALLED=false\n");
        config(['mybsphp.environment_file' => $this->environmentPath]);
    }

    protected function tearDown(): void
    {
        @unlink($this->environmentPath);
        @unlink($this->environmentPath.'.installing');

        parent::tearDown();
    }

    public function test_it_updates_existing_values_and_appends_new_values(): void
    {
        app(EnvironmentFileWriter::class)->update([
            'APP_NAME' => 'My "Dynamic" BSPHP',
            'APP_INSTALLED' => true,
            'ADMIN_PATH' => 'private_admin',
            'DB_PORT' => 3306,
        ]);

        $contents = file_get_contents($this->environmentPath);

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_NAME="My \\"Dynamic\\" BSPHP"', $contents);
        $this->assertStringContainsString('APP_INSTALLED=true', $contents);
        $this->assertStringContainsString('ADMIN_PATH="private_admin"', $contents);
        $this->assertStringContainsString('DB_PORT=3306', $contents);
        $this->assertFileDoesNotExist($this->environmentPath.'.installing');
    }
}
