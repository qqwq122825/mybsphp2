<?php

namespace Tests;

use App\Auth\AdminSession;
use App\Http\Controllers\CardActivationController;
use App\Models\Administrator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($guard === 'admin' && $user instanceof Administrator) {
            // Seed the same server-side token required by a real admin login.
            // Production middleware remains strict, including for legacy sessions.
            $token = bin2hex(random_bytes(32));
            $user->forceFill(['admin_session_token_hash' => hash('sha256', $token)])->save();
            $this->withSession([AdminSession::KEY => $token]);
        }

        return parent::actingAs($user, $guard);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Preserve service-level HTTP coverage without exposing a production
        // plaintext card-activation endpoint.
        Route::post('/__testing/card-activate', [CardActivationController::class, 'activate']);
    }
}
