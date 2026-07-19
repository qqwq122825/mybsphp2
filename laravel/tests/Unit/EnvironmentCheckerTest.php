<?php

namespace Tests\Unit;

use App\Install\EnvironmentChecker;
use Tests\TestCase;

class EnvironmentCheckerTest extends TestCase
{
    public function test_it_reports_required_runtime_and_advisory_https_checks(): void
    {
        $checks = app(EnvironmentChecker::class)->checks();
        $checksByKey = collect($checks)->keyBy('key');

        $this->assertTrue($checksByKey->has('php'));
        $this->assertTrue($checksByKey->has('extension_pdo_mysql'));
        $this->assertTrue($checksByKey->has('directory_storage'));
        $this->assertFalse($checksByKey->get('https')['required']);
        $this->assertFalse(app(EnvironmentChecker::class)->hasBlockingFailure($checks));
    }
}
