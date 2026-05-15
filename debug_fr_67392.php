<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;

$prinect = app(PrinectService::class);
$svc = app(PrinectSyncService::class);

$jobId = '67392';
$wsData = $prinect->getJobWorksteps($jobId);
$ws = collect($wsData['worksteps'] ?? [])
    ->filter(fn($w) => in_array('ConventionalPrinting', $w['types'] ?? []));

echo "=== Worksteps stampa job $jobId ===\n";
foreach ($ws as $w) {
    echo "  name='{$w['name']}' status={$w['status']} prodotti={$w['amountProduced']} scarto={$w['wasteProduced']}\n";
}

$ref = new ReflectionMethod($svc, 'detectFronteRetro');
$ref->setAccessible(true);
$result = $ref->invoke($svc, $ws);
echo "\ndetectFronteRetro = " . ($result ? 'TRUE' : 'FALSE') . "\n";

echo "\nMAX amountProduced: " . $ws->max(fn($w) => $w['amountProduced'] ?? 0) . "\n";
echo "SUM amountProduced: " . $ws->sum(fn($w) => $w['amountProduced'] ?? 0) . "\n";
