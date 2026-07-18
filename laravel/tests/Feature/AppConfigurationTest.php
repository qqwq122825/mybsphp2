<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppConfigurationTest extends TestCase
{
    public function test_application_baseline_configuration(): void
    {
        $this->assertSame('MyBsPHP2', config('app.name'));
        $this->assertSame('Asia/Shanghai', config('app.timezone'));
        $this->assertSame('zh_CN', config('app.locale'));
    }

    public function test_development_environment_example_uses_mysql(): void
    {
        $environment = file_get_contents(base_path('.env.example'));

        $this->assertIsString($environment);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $environment);
        $this->assertStringContainsString('DB_DATABASE=mybsphp_laravel', $environment);
    }
}
