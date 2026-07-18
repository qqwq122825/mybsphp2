<?php
declare(strict_types=1);

use App\Database\Database;
use App\Database\MigrationRunner;
use App\Support\Env;

require dirname(__DIR__) . '/vendor/autoload.php';
Env::load(dirname(__DIR__) . '/.env');

try {
    $executed = (new MigrationRunner(
        Database::connection(),
        dirname(__DIR__) . '/database/migrations'
    ))->run();
    foreach ($executed as $migration) {
        echo "[OK] {$migration}\n";
    }
    echo "Migrations are up to date.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "[FAIL] {$exception->getMessage()}\n");
    exit(1);
}
