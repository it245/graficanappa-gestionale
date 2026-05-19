<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\PrinectService;

$prinect = app(PrinectService::class);
$jobId = 66956;

echo "=== JOB {$jobId} — TUTTI workstep ===\n";
$jobData = $prinect->getJobWorksteps($jobId);
$worksteps = $jobData['worksteps'] ?? [];
echo "Totale worksteps: " . count($worksteps) . "\n\n";

$copertinaWs = [];
foreach ($worksteps as $ws) {
    $name = $ws['name'] ?? '?';
    $types = implode(',', $ws['types'] ?? []);
    $status = $ws['status'] ?? '?';
    $produced = $ws['amountProduced'] ?? 0;
    $scarto = $ws['wasteProduced'] ?? 0;
    $start = $ws['start'] ?? $ws['actualStartDate'] ?? '';
    $end = $ws['end'] ?? $ws['actualEndDate'] ?? '';
    echo "  {$name} | types={$types} | {$status} | prod={$produced} | scarto={$scarto} | {$start} → {$end}\n";

    if (stripos($name, 'COPERTINA') !== false) {
        $copertinaWs[] = $ws;
    }
}

echo "\n=== WORKSTEP COPERTINA dettaglio completo ===\n";
foreach ($copertinaWs as $ws) {
    echo "\n>>> {$ws['name']} (id={$ws['id']})\n";
    echo "Full: " . json_encode($ws, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // Activities
    try {
        $act = $prinect->getWorkstepActivities($jobId, $ws['id']);
        echo "\nActivities: " . json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (\Throwable $e) { echo "Activities ERR: " . $e->getMessage() . "\n"; }

    // Quality
    try {
        $q = $prinect->getWorkstepQuality($jobId, $ws['id']);
        echo "\nQuality: " . json_encode($q, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (\Throwable $e) { echo "Quality ERR: " . $e->getMessage() . "\n"; }
}
