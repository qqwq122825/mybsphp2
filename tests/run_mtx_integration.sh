#!/bin/bash
# Local-only test runner. Verify the effective DB config before RefreshDatabase.
set -euo pipefail
cd "$(dirname "$0")/.."
PHP_BIN=${PHP_BIN:-php}
export APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=mybsphp_test DB_URL=
"$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = Illuminate\Support\Facades\DB::connection();
if ($connection->getConfig("host") !== "127.0.0.1"
    || $connection->getDatabaseName() !== "mybsphp_test"
    || !empty($connection->getConfig("url"))) {
    fwrite(STDERR, "Test database isolation check failed.\n"); exit(2);
}
if ($connection->selectOne("select database() as db")->db !== "mybsphp_test") exit(3);
echo "Verified local test database: 127.0.0.1/mybsphp_test\n";
'
exec "$PHP_BIN" vendor/bin/phpunit "$@"
