<?php

namespace App\Install;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SystemAccountResetter
{
    /**
     * @var list<string>
     */
    private const TABLES = [
        'administrator_roles',
        'role_permissions',
        'administrators',
        'roles',
        'permissions',
        'audit_logs',
        'system_settings',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function reset(?string $connection = null): void
    {
        $database = DB::connection($connection);
        $schema = Schema::connection($database->getName());

        $database->transaction(function () use ($database, $schema): void {
            foreach (self::TABLES as $table) {
                if ($schema->hasTable($table)) {
                    $database->table($table)->delete();
                }
            }
        }, 3);
    }
}
