<?php

namespace Tests\Feature;

use App\Auth\AdminPermission;
use App\Auth\SystemAccessSynchronizer;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAccessSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_roles_and_permissions_are_synchronized_idempotently(): void
    {
        $synchronizer = app(SystemAccessSynchronizer::class);
        $synchronizer->sync();
        $synchronizer->sync();

        $this->assertDatabaseHas('roles', ['code' => 'agent', 'is_system' => true]);
        $this->assertSame(count(AdminPermission::cases()), (int) \DB::table('permissions')->count());

        $super = Role::query()->where('code', 'super_admin')->sole();
        $admin = Role::query()->where('code', 'admin')->sole();
        $agent = Role::query()->where('code', 'agent')->sole();

        $this->assertSame(count(AdminPermission::cases()), $super->permissions()->count());
        $this->assertTrue($admin->permissions()->where('code', AdminPermission::ApplicationsCreate->value)->exists());
        $this->assertFalse($admin->permissions()->where('code', AdminPermission::CardsDelete->value)->exists());
        $this->assertTrue($agent->permissions()->where('code', AdminPermission::CardsIssue->value)->exists());
        $this->assertFalse($agent->permissions()->where('code', AdminPermission::DevicesUnbind->value)->exists());
        $this->assertFalse($agent->permissions()->where('code', AdminPermission::AppAccountsManage->value)->exists());
    }
}
