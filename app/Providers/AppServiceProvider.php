<?php

namespace App\Providers;

use App\Auth\AdminPermission;
use App\Models\Administrator;
use App\Models\SystemSetting;
use App\Policies\AdministratorPolicy;
use App\Support\SensitiveDataRedactor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SensitiveDataRedactor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            $siteSettings = SystemSetting::query()
                ->whereIn('key', ['app_name', 'app_url', 'app_timezone'])
                ->pluck('value', 'key');
            if ($siteSettings->has('app_name')) {
                config(['app.name' => (string) $siteSettings['app_name']]);
            }
            if ($siteSettings->has('app_url')) {
                config(['app.url' => rtrim((string) $siteSettings['app_url'], '/')]);
            }
            $timezone = (string) ($siteSettings['app_timezone'] ?? '');
            if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (Throwable) {
            // Setup pages and repair commands run before a settings table is available.
        }

        foreach (AdminPermission::cases() as $permission) {
            Gate::define(
                $permission->value,
                static fn (Administrator $administrator): bool => $administrator->status === 'active'
                    && $administrator->hasPermission($permission->value),
            );
        }

        Gate::policy(Administrator::class, AdministratorPolicy::class);
    }
}
