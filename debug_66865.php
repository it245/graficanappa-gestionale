<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Services\PrinectService;
use App\Models\OrdineFase;
use App\Models\PrinectAttivita;

$commessa = '0066865-26';
$jobId = '66865';
$prinect = app(PrinectService::class);

echo "=== DEBUG {$commessa} ===" . PHP_EOL;

// Fasi MES
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with('ordine')
    ->get();

echo "Fasi MES:" . PHP_EOL;
foreach ($fasi as $f) {
    echo "  {$f->fase} | stato:{$f->stato} | fogli:{$f->fogli_buoni} | inizio:" . ($f->data_inizio ?? '-') . " | fine:" . ($f->data_fine ?? '-') . PHP_EOL;
}

// Attività Prinect nel MES
$attCount = PrinectAttivita::where('commessa_gestionale', $commessa)->count();
$ultimaAtt = PrinectAttivita::where('commessa_gestionale', $commessa)->orderByDesc('start_time')->first();
echo PHP_EOL . "Attività Prinect MES: {$attCount}" . PHP_EOL;
if ($ultimaAtt) echo "Ultima: {$ultimaAtt->start_time} | buoni:{$ultimaAtt->good_cycles}" . PHP_EOL;

// Workstep Prinect API
echo PHP_EOL . "Workstep Prinect API:" . PHP_EOL;
try {
    $wsData = $prinect->getJobWorksteps($jobId);
    $worksteps = collect($wsData['worksteps'] ?? [])
        ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

    foreach ($worksteps as $ws) {
        echo "  {$ws['name']} | status:{$ws['status']} | buoni:" . ($ws['amountProduced'] ?? 0) . " | scarti:" . ($ws['wasteProduced'] ?? 0) . PHP_EOL;
        echo "    actualStart:" . ($ws['actualStartDate'] ?? 'NULL') . " | actualEnd:" . ($ws['actualEndDate'] ?? 'NULL') . PHP_EOL;

        // Workstep activities
        $wsAct = $prinect->getWorkstepActivities($jobId, $ws['id']);
        $activities = $wsAct['activities'] ?? [];
        echo "    WS Activities: " . count($activities) . PHP_EOL;
        foreach (array_slice($activities, 0, 3) as $a) {
            echo "      {$a['startTime']} | buoni:" . ($a['goodCycles'] ?? 0) . " | tipo:" . ($a['timeTypeName'] ?? '?') . PHP_EOL;
        }
    }

    $allCompleted = $worksteps->every(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');
    $anyWaiting = $worksteps->contains(fn($ws) => ($ws['status'] ?? '') === 'WAITING');
    $totalBuoni = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);

    echo PHP_EOL . "allCompleted: " . ($allCompleted ? 'SI' : 'NO') . PHP_EOL;
    echo "anyWaiting: " . ($anyWaiting ? 'SI' : 'NO') . PHP_EOL;
    echo "totaleBuoni: {$totalBuoni}" . PHP_EOL;
} catch (\Exception $e) {
    echo "ERRORE API: " . $e->getMessage() . PHP_EOL;
}
