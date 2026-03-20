<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Services\PrinectService;

$prinect = app(PrinectService::class);
$commesse = ['66513', '66516', '66654', '66736', '66760', '66833'];

foreach ($commesse as $jobId) {
    echo "=== JOB {$jobId} ===" . PHP_EOL;

    // Job info
    $job = $prinect->getJob($jobId);
    $jobData = $job['job'] ?? $job ?? [];
    echo "  Nome: " . ($jobData['name'] ?? 'N/A') . PHP_EOL;
    echo "  Status: " . ($jobData['status'] ?? 'N/A') . PHP_EOL;

    // Workstep
    $wsData = $prinect->getJobWorksteps($jobId);
    $worksteps = collect($wsData['worksteps'] ?? [])
        ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

    if ($worksteps->isEmpty()) {
        echo "  Nessun workstep ConventionalPrinting" . PHP_EOL . PHP_EOL;
        continue;
    }

    foreach ($worksteps as $ws) {
        echo "  WS: {$ws['name']} | status: {$ws['status']} | buoni: " . ($ws['amountProduced'] ?? 0) . " | scarti: " . ($ws['wasteProduced'] ?? 0) . PHP_EOL;
        echo "    actualStart: " . ($ws['actualStartDate'] ?? 'NULL') . " | actualEnd: " . ($ws['actualEndDate'] ?? 'NULL') . PHP_EOL;

        // Workstep activities
        try {
            $wsAct = $prinect->getWorkstepActivities($jobId, $ws['id']);
            $activities = $wsAct['activities'] ?? [];
            echo "    Attività workstep: " . count($activities) . PHP_EOL;
            foreach ($activities as $a) {
                $buoni = $a['goodCycles'] ?? 0;
                $scarti = $a['wasteCycles'] ?? 0;
                $tipo = $a['timeTypeName'] ?? '?';
                $op = $a['employees'][0]['name'] ?? '?';
                $start = $a['startTime'] ?? '?';
                echo "      {$start} | buoni:{$buoni} scarti:{$scarti} | {$tipo} | {$op}" . PHP_EOL;
            }
            if (empty($activities)) echo "      (nessuna attività)" . PHP_EOL;
        } catch (\Exception $e) {
            echo "    ERRORE API: " . $e->getMessage() . PHP_EOL;
        }
    }
    echo PHP_EOL;
}
