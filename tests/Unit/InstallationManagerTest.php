<?php

namespace Tests\Unit;

use App\Install\InstallationManager;
use Tests\TestCase;

class InstallationManagerTest extends TestCase
{
    private string $lockPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = storage_path('framework/testing/installation-manager.lock');
        @unlink($this->lockPath);
        config([
            'mybsphp.installed' => false,
            'mybsphp.install_lock' => $this->lockPath,
            'mybsphp.admin_path' => 'admin',
            'mybsphp.secondary_password_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);

        parent::tearDown();
    }

    public function test_it_uses_uninstalled_defaults_without_a_lock(): void
    {
        $manager = app(InstallationManager::class);

        $this->assertFalse($manager->isInstalled());
        $this->assertSame('admin', $manager->adminPath());
        $this->assertFalse($manager->secondaryPasswordEnabled());
    }

    public function test_lock_file_is_the_only_installation_switch(): void
    {
        config(['mybsphp.installed' => true]);

        $manager = app(InstallationManager::class);

        $this->assertFalse($manager->isInstalled());
        $this->assertSame('storage/framework/testing/installation-manager.lock', $manager->lockFilePath());
    }

    public function test_it_atomically_persists_and_reads_installation_metadata(): void
    {
        $manager = app(InstallationManager::class);
        $manager->writeLock([
            'admin_path' => 'private-console',
            'app_name' => '动态授权平台',
            'secondary_password_enabled' => true,
        ]);

        $this->assertTrue($manager->isInstalled());
        $this->assertSame('private-console', $manager->adminPath());
        $this->assertSame('动态授权平台', $manager->applicationName());
        $this->assertTrue($manager->secondaryPasswordEnabled());
        $this->assertSame('0.2.0-dev', $manager->metadata()['version']);
        $this->assertFileExists($this->lockPath);
    }
}
