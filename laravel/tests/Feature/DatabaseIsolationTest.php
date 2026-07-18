<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\UserFixture;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_and_fixture_write_to_the_test_database(): void
    {
        $user = User::factory()->create(UserFixture::attributes());

        $this->assertSame('mybsphp_test', $user->getConnection()->getDatabaseName());
        $this->assertDatabaseHas('users', [
            'email' => 'fixture.user@example.test',
        ]);
    }
}
