<?php

declare(strict_types=1);

// Compatibility shim for the former /agent/index.php entry. Nginx resolves
// *.php before Laravel, so this physical file must perform the redirect.
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/agent/index.php'));
$basePath = rtrim(dirname($scriptName), '/.');
$target = ($basePath === '' ? '' : $basePath).'/login';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$status = in_array($method, ['GET', 'HEAD'], true) ? 302 : 307;

header('Location: '.$target, true, $status);
exit;
