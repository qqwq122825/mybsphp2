<?php

declare(strict_types=1);

// A physical PHP entry point keeps POST/OPTIONS intact on Nginx configurations
// that route missing *.php files through an internal GET error-page rewrite.
// Present it as the front controller so Symfony keeps /AppEn.php as path info.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/index.php';

require __DIR__.'/index.php';
