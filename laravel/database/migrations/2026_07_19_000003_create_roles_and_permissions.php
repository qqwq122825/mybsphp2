<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('module', 60)->index();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('risk_level', 24)->default('normal')->index();
            $table->boolean('agent_assignable')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('administrator_roles', function (Blueprint $table): void {
            $table->foreignId('administrator_id')
                ->constrained('administrators')
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->foreignId('assigned_by_administrator_id')
                ->nullable()
                ->constrained('administrators')
                ->nullOnDelete();
            $table->timestamps();

            $table->primary(['administrator_id', 'role_id']);
            $table->index(['role_id', 'administrator_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->index(['permission_id', 'role_id']);
        });

        $now = now();
        DB::table('roles')->insert([
            [
                'code' => 'super_admin',
                'name' => '超级管理员',
                'description' => '平台最高管理角色，拥有全部后台能力。',
                'is_system' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'admin',
                'name' => '管理员',
                'description' => '按分配权限执行后台管理操作。',
                'is_system' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $roleIds = DB::table('roles')
            ->whereIn('code', ['super_admin', 'admin'])
            ->pluck('id', 'code');

        DB::table('administrators')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $administrator) use ($roleIds, $now): void {
                $legacyRole = $administrator->role === 'super_admin' ? 'super_admin' : 'admin';
                $roleId = $roleIds->get($legacyRole);

                if (is_int($roleId) || (is_string($roleId) && ctype_digit($roleId))) {
                    DB::table('administrator_roles')->insertOrIgnore([
                        'administrator_id' => $administrator->id,
                        'role_id' => (int) $roleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('administrator_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
