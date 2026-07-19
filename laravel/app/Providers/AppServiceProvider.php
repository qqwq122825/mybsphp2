<?php

namespace App\Providers;

use App\Auth\AdminPermission;
use App\Models\Administrator;
use App\Policies\AdministratorPolicy;
use App\Support\SensitiveDataRedactor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
