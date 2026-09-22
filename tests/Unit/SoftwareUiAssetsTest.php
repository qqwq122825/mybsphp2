<?php

namespace Tests\Unit;

use App\Admin\SoftwareUiAssets;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SoftwareUiAssetsTest extends TestCase
{
    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDirectory = sys_get_temp_dir().'/software-ui-'.bin2hex(random_bytes(8));
        mkdir($this->publicDirectory.'/build/assets', 0700, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }

    private function stamp(): void
    {
        $contract = json_decode(file_get_contents(resource_path('js/admin-ui/build-contract.json')), true);
        file_put_contents($this->publicDirectory.'/build/software-ui-version.json', json_encode([
            ...$contract, 'manifestSha256' => hash_file('sha256', $this->publicDirectory.'/build/manifest.json'),
        ]));
    }

    private function build(): void
    {
        $manifest = [
            'resources/js/admin-ui.js' => ['file' => 'assets/admin.js', 'imports' => ['_form.js']],
            '_form.js' => ['file' => 'assets/form.js', 'css' => ['assets/form.css']],
        ];
        file_put_contents($this->publicDirectory.'/build/manifest.json', json_encode($manifest));
        foreach (['admin.js', 'form.js', 'form.css'] as $name) {
            file_put_contents($this->publicDirectory.'/build/assets/'.$name, 'test-fixture');
        }
        $this->stamp();
    }

    public function test_missing_or_unstamped_assets_use_the_server_form(): void
    {
        $checker = new SoftwareUiAssets;
        $this->assertFalse($checker->ready($this->publicDirectory));
        $this->build();
        unlink($this->publicDirectory.'/build/software-ui-version.json');
        $this->assertFalse($checker->ready($this->publicDirectory));
    }

    public function test_matching_build_is_ready_but_missing_imported_assets_are_not(): void
    {
        $this->build();
        $checker = new SoftwareUiAssets;
        $this->assertTrue($checker->ready($this->publicDirectory));
        unlink($this->publicDirectory.'/build/assets/form.js');
        $this->assertFalse($checker->ready($this->publicDirectory));
    }

    public function test_mixed_manifest_or_old_revision_or_malformed_stamp_is_not_ready(): void
    {
        $checker = new SoftwareUiAssets;
        $this->build();
        file_put_contents($this->publicDirectory.'/build/manifest.json', ' ', FILE_APPEND);
        $this->assertFalse($checker->ready($this->publicDirectory));
        $this->stamp();
        $this->assertTrue($checker->ready($this->publicDirectory));
        $path = $this->publicDirectory.'/build/software-ui-version.json';
        $data = json_decode(file_get_contents($path), true);
        $data['softwareForm'] = 'old';
        file_put_contents($path, json_encode($data));
        $this->assertFalse($checker->ready($this->publicDirectory));
        file_put_contents($path, 'broken-json');
        $this->assertFalse($checker->ready($this->publicDirectory));
    }

    public function test_vite_development_server_can_render_current_source(): void
    {
        file_put_contents($this->publicDirectory.'/hot', 'http://localhost:5173');
        $this->assertTrue((new SoftwareUiAssets)->ready($this->publicDirectory));
    }
}
