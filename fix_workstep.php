<?php
// Aggiorna fogli_buoni dal workstep Prinect per una commessa
// Uso: php fix_workstep.php 66792
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$jobId = $argv[1] ?? '66792';
$commessa = '00' . str_pad($jobId, 5, '0', STR_PAD_LEFT) . '-26';

echo "Job: $jobId | Commessa: $commessa\n";

$prinect = app('App\Http\Services\PrinectService');
$wsData = $prinect->getJobWorksteps($jobId);

$worksteps = collect($wsData['worksteps'] ?? [])
    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

echo "Worksteps stampa: " . $worksteps->count() . "\n";

foreach ($worksteps as $ws) {
    echo "  {$ws['name']} | Stato: {$ws['status']} | Prodotti: " . ($ws['amountProduced'] ?? 0) . " | Scarto: " . ($ws['wasteProduced'] ?? 0) . "\n";
}

$totaleBuoni = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);
$totaleScarto = $worksteps->sum(fn($ws) => $ws['wasteProduced'] ?? 0);
echo "\nTotale buoni: $totaleBuoni | Totale scarto: $totaleScarto\n";

// Aggiorna fasi
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where(fn($q) => $q->where('fase', 'LIKE', 'STAMPAXL106%')->orWhere('fase', 'STAMPA')->orWhere('fase', 'LIKE', 'STAMPA XL%'))
    ->get();

foreach ($fasi as $fase) {
    echo "\nFase: {$fase->fase} | Stato: {$fase->stato} | Fogli DB: {$fase->fogli_buoni}\n";
    if ($totaleBuoni > ($fase->fogli_buoni ?? 0)) {
        $fase->fogli_buoni = $totaleBuoni;
        $fase->qta_prod = $totaleBuoni;
        $fase->fogli_scarto = $totaleScarto;
        $fase->save();
        echo "  → Aggiornato a $totaleBuoni fogli buoni\n";
    } else {
        echo "  → Già aggiornato\n";
    }
}
