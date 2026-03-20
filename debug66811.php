<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Services\PrinectService;
use App\Models\OrdineFase;
use App\Models\PrinectAttivita;

$prinect = app(PrinectService::class);
$commessa = '0066811-26';
$jobId = '66811';

echo "=== DEBUG COMMESSA {$commessa} ===" . PHP_EOL;

// 1. Fasi nel MES
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with('ordine')
    ->where(function ($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    ->get();

echo "Fasi stampa trovate: " . $fasi->count() . PHP_EOL;
foreach ($fasi as $f) {
    echo "  {$f->fase} | stato:{$f->stato} | fogli_buoni:{$f->fogli_buoni} | qta_carta:" . ($f->ordine->qta_carta ?? 'NULL') . PHP_EOL;
}

if ($fasi->isEmpty()) {
    echo "NESSUNA FASE STAMPA TROVATA - ecco perchè non si chiude!" . PHP_EOL;

    // Mostra tutte le fasi
    $tutteFasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))->get();
    echo PHP_EOL . "Tutte le fasi:" . PHP_EOL;
    foreach ($tutteFasi as $f) {
        echo "  {$f->fase} | stato:{$f->stato}" . PHP_EOL;
    }
    exit;
}

// 2. Workstep Prinect
try {
    $wsData = $prinect->getJobWorksteps($jobId);
    $worksteps = collect($wsData['worksteps'] ?? [])
        ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

    echo PHP_EOL . "Workstep ConventionalPrinting: " . $worksteps->count() . PHP_EOL;
    foreach ($worksteps as $ws) {
        echo "  {$ws['name']} | status:{$ws['status']} | buoni:{$ws['amountProduced']} | scarto:{$ws['wasteProduced']}"
            . " | start:" . ($ws['actualStartDate'] ?? 'NULL')
            . " | end:" . ($ws['actualEndDate'] ?? 'NULL')
            . PHP_EOL;
    }

    $wsConStart = $worksteps->filter(fn($ws) => !empty($ws['actualStartDate']));
    echo PHP_EOL . "Workstep con actualStartDate: " . $wsConStart->count() . PHP_EOL;

    $totaleBuoniWs = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);
    $totaleScartaWs = $worksteps->sum(fn($ws) => $ws['wasteProduced'] ?? 0);
    echo "Totale buoni workstep: {$totaleBuoniWs}" . PHP_EOL;
    echo "Totale scarti workstep: {$totaleScartaWs}" . PHP_EOL;

    // 3. Attivita MES
    $attivitaCount = PrinectAttivita::where('commessa_gestionale', $commessa)->count();
    $ultimaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
        ->orderByDesc('start_time')
        ->value('start_time');
    echo PHP_EOL . "Attivita nel MES: {$attivitaCount}" . PHP_EOL;
    echo "Ultima attivita: " . ($ultimaAttivita ?? 'NESSUNA') . PHP_EOL;

    if ($ultimaAttivita) {
        $minFa = \Carbon\Carbon::parse($ultimaAttivita)->diffInMinutes(now());
        echo "Minuti fa: {$minFa} (protezione A se < 60)" . PHP_EOL;
    }

    // 4. Check regole
    $allCompleted = $worksteps->every(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');
    $anyWaiting = $worksteps->contains(fn($ws) => ($ws['status'] ?? '') === 'WAITING');
    echo PHP_EOL . "allCompleted: " . ($allCompleted ? 'SI' : 'NO') . PHP_EOL;
    echo "anyWaiting: " . ($anyWaiting ? 'SI' : 'NO') . PHP_EOL;

    $qtaCarta = $fasi->first()->ordine->qta_carta ?? 0;
    echo "qta_carta ordine: {$qtaCarta}" . PHP_EOL;

    echo PHP_EOL . "=== CONCLUSIONE ===" . PHP_EOL;
    if ($wsConStart->isEmpty()) echo "BLOCCO: nessun workstep con actualStartDate" . PHP_EOL;
    if ($totaleBuoniWs <= 0) echo "BLOCCO: totaleBuoniWs = 0" . PHP_EOL;
    if ($ultimaAttivita && $minFa < 60) echo "BLOCCO: protezione A (attivita recente {$minFa}m fa)" . PHP_EOL;
    if ($allCompleted) echo "OK: Regola 1 (tutti COMPLETED) -> dovrebbe terminare" . PHP_EOL;
    if (!$allCompleted && !$anyWaiting && $totaleBuoniWs >= $qtaCarta && $qtaCarta > 0) echo "OK: Regola 2 (buoni >= qta_carta)" . PHP_EOL;

} catch (\Exception $e) {
    echo "ERRORE API: " . $e->getMessage() . PHP_EOL;
}
