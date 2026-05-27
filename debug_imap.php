<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SimpleWebklexImapService;

$svc = new SimpleWebklexImapService();
$diag = $svc->diagnosticar();
echo json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
