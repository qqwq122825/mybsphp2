<?php

namespace Tests;

use App\Http\Controllers\CardActivationController;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Route;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Preserve service-level HTTP coverage without exposing a production
        // plaintext card-activation endpoint.
        Route::post('/__testing/card-activate', [CardActivationController::class, 'activate']);
    }
}
