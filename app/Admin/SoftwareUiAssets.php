<?php

namespace App\Admin;

class SoftwareUiAssets
{
    /** Keep the server form visible when a Git-only deployment still has old bundles. */
    public function ready(?string $publicDirectory = null): bool
    {
        $publicDirectory ??= public_path();
        if (is_file($publicDirectory.'/hot')) {
            return true;
        }
        $build = $publicDirectory.'/build/';
        $contract = $this->readJson(resource_path('js/admin-ui/build-contract.json'));
        $stamp = $this->readJson($build.'software-ui-version.json');
        $manifest = $this->readJson($build.'manifest.json');
        if (! isset($contract['softwareForm'], $stamp['softwareForm'], $stamp['manifestSha256'])
            || $contract['softwareForm'] !== $stamp['softwareForm']
            || ! is_file($build.'manifest.json')
            || hash_file('sha256', $build.'manifest.json') !== $stamp['manifestSha256']) {
            return false;
        }
        $pending = ['resources/js/admin-ui.js'];
        $seen = [];
        while ($key = array_pop($pending)) {
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $entry = $manifest[$key] ?? null;
            if (! is_array($entry) || ! is_string($entry['file'] ?? null)) {
                return false;
            }
            $styles = $entry['css'] ?? [];
            $imports = $entry['imports'] ?? [];
            if (! is_array($styles) || ! is_array($imports)) {
                return false;
            }
            foreach ([$entry['file'], ...$styles] as $file) {
                if (! is_string($file) || ! str_starts_with($file, 'assets/')
                    || str_contains($file, '..') || ! is_file($build.$file)) {
                    return false;
                }
            }
            foreach ($imports as $dependency) {
                if (! is_string($dependency)) {
                    return false;
                }
                $pending[] = $dependency;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function readJson(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }
        $contents = file_get_contents($path);
        $value = $contents === false ? null : json_decode($contents, true);

        return is_array($value) ? $value : [];
    }
}
