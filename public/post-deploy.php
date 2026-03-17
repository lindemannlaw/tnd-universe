<?php
if (($_GET['token'] ?? '') !== 'tnd-pd-2026') {
    http_response_code(403);
    die('Forbidden');
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<pre>\n";

$kernel->call('migrate', ['--force' => true]);
echo $kernel->output();

$kernel->call('storage:link', ['--force' => true]);
echo $kernel->output();

$kernel->call('view:clear');
echo $kernel->output();

$kernel->call('cache:clear');
echo $kernel->output();

echo "Done.\n</pre>";
