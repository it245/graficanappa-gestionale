<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ClicheMatchService;

$r = ClicheMatchService::matchAll();
echo "Re-match completato: matched={$r['matched']} updated={$r['updated']}\n";
