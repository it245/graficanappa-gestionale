<?php
/**
 * Dump RAW risposta Prinect inkConsumption per capire unità + struttura.
 * Uso: php check_ink_raw.php 0067100-26
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Services\PrinectService;

$commessa = $argv[1] ?? '0067100-26';
$jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
$prinect = app(PrinectService::class);

echo "=== Commessa {$commessa} jobId={$jobId} ===\n\n";

$wsData = $prinect->getJobWorksteps($jobId);
$worksteps = collect($wsData['worksteps'] ?? [])
    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

foreach ($worksteps as $ws) {
    echo "--- Workstep {$ws['id']} name={$ws['name']} status={$ws['status']} ---\n";
    echo "amountProduced={$ws['amountProduced']} wasteProduced={$ws['wasteProduced']}\n";

    $ink = $prinect->getWorkstepInkConsumption($jobId, $ws['id']);
    echo "\ninkConsumption raw:\n";
    echo json_encode($ink, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}
