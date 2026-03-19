<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$jobId = $argv[1] ?? '66792';

$prinect = app('App\Http\Services\PrinectService');
$wsData = $prinect->getJobWorksteps($jobId);

if (!$wsData) {
    echo "Errore: nessun dato per job $jobId\n";
    exit;
}

echo "=== WORKSTEPS JOB $jobId ===\n\n";

foreach ($wsData['worksteps'] ?? [] as $ws) {
    $types = implode(', ', $ws['types'] ?? []);
    echo "Nome: {$ws['name']} | Tipo: {$types} | Stato: {$ws['status']}\n";
    echo "  Campi numerici:\n";
    foreach ($ws as $key => $val) {
        if (is_numeric($val) && $val > 0) {
            echo "    $key = $val\n";
        }
    }
    echo "\n";
}
