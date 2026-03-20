<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Services\PrinectService;

$prinect = app(PrinectService::class);
$jobId = $argv[1] ?? '66811';

echo "=== TEST ENDPOINT PRINECT — JOB {$jobId} ===" . PHP_EOL . PHP_EOL;

// 1. /rest/job/{jobId} — stato job completo
echo "--- 1. GET /rest/job/{$jobId} ---" . PHP_EOL;
$job = $prinect->getJob($jobId);
if ($job) {
    echo "  name: " . ($job['name'] ?? 'NULL') . PHP_EOL;
    echo "  status: " . ($job['status'] ?? 'NULL') . PHP_EOL;
    echo "  productionStartDate: " . ($job['productionStartDate'] ?? 'NULL') . PHP_EOL;
    echo "  productionEndDate: " . ($job['productionEndDate'] ?? 'NULL') . PHP_EOL;
    echo "  creationDate: " . ($job['creationDate'] ?? 'NULL') . PHP_EOL;
    echo "  milestoneProgress: " . json_encode($job['milestoneProgress'] ?? 'NULL') . PHP_EOL;
    // Mostra tutte le chiavi top-level
    echo "  CHIAVI DISPONIBILI: " . implode(', ', array_keys($job)) . PHP_EOL;
} else {
    echo "  ERRORE: risposta null" . PHP_EOL;
}

echo PHP_EOL;

// 2. /rest/job/{jobId}/workstep — lista workstep
echo "--- 2. GET /rest/job/{$jobId}/workstep ---" . PHP_EOL;
$wsData = $prinect->getJobWorksteps($jobId);
$worksteps = collect($wsData['worksteps'] ?? [])
    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

foreach ($worksteps as $ws) {
    $wsId = $ws['id'] ?? '?';
    echo "  WS: {$ws['name']} | id: {$wsId}" . PHP_EOL;
    echo "    status: {$ws['status']} | amountProduced: " . ($ws['amountProduced'] ?? 0) . " | wasteProduced: " . ($ws['wasteProduced'] ?? 0) . PHP_EOL;
    echo "    actualStartDate: " . ($ws['actualStartDate'] ?? 'NULL') . PHP_EOL;
    echo "    actualEndDate: " . ($ws['actualEndDate'] ?? 'NULL') . PHP_EOL;
    echo "    CHIAVI WS: " . implode(', ', array_keys($ws)) . PHP_EOL;

    // 3. /rest/job/{jobId}/workstep/{wsId}/activity — attività per workstep
    echo "  --- 3. GET /rest/job/{$jobId}/workstep/{$wsId}/activity ---" . PHP_EOL;
    try {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            env('PRINECT_API_USER'), env('PRINECT_API_PASS')
        )->timeout(15)->get(
            env('PRINECT_API_URL') . "/rest/job/{$jobId}/workstep/{$wsId}/activity"
        );
        if ($response->successful()) {
            $wsAct = $response->json();
            $activities = $wsAct['activities'] ?? [];
            echo "    Attività trovate: " . count($activities) . PHP_EOL;
            foreach (array_slice($activities, 0, 5) as $a) {
                echo "      " . ($a['startTime'] ?? '?') . " → " . ($a['endTime'] ?? '?')
                    . " | buoni: " . ($a['goodCycles'] ?? 0)
                    . " | scarti: " . ($a['wasteCycles'] ?? 0)
                    . " | tipo: " . ($a['timeTypeName'] ?? '?')
                    . " | op: " . (($a['employees'][0]['name'] ?? '') ?: '?')
                    . PHP_EOL;
            }
            if (count($activities) > 5) echo "      ... e altre " . (count($activities) - 5) . PHP_EOL;
        } else {
            echo "    HTTP " . $response->status() . ": " . substr($response->body(), 0, 200) . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "    ERRORE: " . $e->getMessage() . PHP_EOL;
    }

    // 4. /rest/job/{jobId}/workstep/{wsId}/inkConsumption
    echo "  --- 4. GET /rest/job/{$jobId}/workstep/{$wsId}/inkConsumption ---" . PHP_EOL;
    try {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            env('PRINECT_API_USER'), env('PRINECT_API_PASS')
        )->timeout(15)->get(
            env('PRINECT_API_URL') . "/rest/job/{$jobId}/workstep/{$wsId}/inkConsumption"
        );
        if ($response->successful()) {
            $ink = $response->json();
            echo "    goodSheets: " . ($ink['goodSheets'] ?? 'N/A') . PHP_EOL;
            echo "    wasteSheets: " . ($ink['wasteSheets'] ?? 'N/A') . PHP_EOL;
            echo "    CHIAVI: " . implode(', ', array_keys($ink)) . PHP_EOL;
        } else {
            echo "    HTTP " . $response->status() . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "    ERRORE: " . $e->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;
}

// 5. /rest/job?modifiedSince — lista job modificati (ultimi 2 giorni)
echo "--- 5. GET /rest/job?modifiedSince (ultimi 2gg) ---" . PHP_EOL;
try {
    $since = date('Y-m-d\TH:i:sP', strtotime('-2 days'));
    $response = \Illuminate\Support\Facades\Http::withBasicAuth(
        env('PRINECT_API_USER'), env('PRINECT_API_PASS')
    )->timeout(30)->get(
        env('PRINECT_API_URL') . "/rest/job",
        ['modifiedSince' => $since]
    );
    if ($response->successful()) {
        $jobs = $response->json()['jobs'] ?? [];
        echo "  Job modificati ultimi 2gg: " . count($jobs) . PHP_EOL;
        foreach (array_slice($jobs, 0, 5) as $j) {
            echo "    {$j['id']} | {$j['name']} | status: " . ($j['status'] ?? '?') . PHP_EOL;
        }
        if (count($jobs) > 5) echo "    ... e altri " . (count($jobs) - 5) . PHP_EOL;
    } else {
        echo "  HTTP " . $response->status() . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . PHP_EOL;
}
