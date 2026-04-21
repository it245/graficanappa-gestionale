<?php
/**
 * Verifica consumi inchiostro Prinect: dumpa ultime N commesse con dettaglio CMYK + coerenza g/foglio.
 * Uso:
 *   php check_inchiostri_prinect.php              → ultime 20 commesse
 *   php check_inchiostri_prinect.php 0066575-26   → singola commessa
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;

$prinect = app(PrinectService::class);
$target = $argv[1] ?? null;

$query = OrdineFase::with('ordine')
    ->whereHas('ordine')
    ->whereHas('faseCatalogo.reparto', fn($q) => $q->where('nome', 'stampa offset'))
    ->whereIn('stato', [3, 4])
    ->where('fogli_buoni', '>', 0);

if ($target) {
    $query->whereHas('ordine', fn($q) => $q->where('commessa', $target));
} else {
    $query->orderByDesc('data_fine')->limit(20);
}

$fasi = $query->get();

if ($fasi->isEmpty()) {
    echo "Nessuna fase trovata.\n";
    exit;
}

printf("%-14s %-18s %-8s %-8s %-8s %-10s %-10s %-8s\n",
    'COMMESSA', 'FASE', 'FOGLI', 'G_TOT', 'G_C', 'G_M', 'G_Y', 'G_K');
echo str_repeat('-', 100) . "\n";

$commesseFatte = [];
foreach ($fasi as $fase) {
    $commessa = $fase->ordine->commessa ?? '-';
    if (isset($commesseFatte[$commessa])) continue;
    $commesseFatte[$commessa] = true;

    $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
    if (!$jobId) continue;

    try {
        $wsData = $prinect->getJobWorksteps($jobId);
        $worksteps = collect($wsData['worksteps'] ?? [])
            ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
            ->filter(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');

        if ($worksteps->isEmpty()) {
            printf("%-14s %-18s %-8d %s\n", $commessa, $fase->fase, $fase->fogli_buoni, 'nessun workstep COMPLETED');
            continue;
        }

        $totals = ['C' => 0, 'M' => 0, 'Y' => 0, 'K' => 0, 'Altro' => 0];
        $totGen = 0;
        foreach ($worksteps as $ws) {
            $ink = $prinect->getWorkstepInkConsumption($jobId, $ws['id']);
            foreach (($ink['inkConsumptions'] ?? []) as $c) {
                // Prinect restituisce in kg → moltiplico per 1000 per grammi
                $g = ((float) ($c['estimatedConsumption'] ?? 0)) * 1000;
                $color = strtolower($c['color'] ?? '');
                $totGen += $g;
                if (str_contains($color, 'cyan')) $totals['C'] += $g;
                elseif (str_contains($color, 'magenta')) $totals['M'] += $g;
                elseif (str_contains($color, 'yellow')) $totals['Y'] += $g;
                elseif (str_contains($color, 'black') || str_contains($color, 'nero')) $totals['K'] += $g;
                else $totals['Altro'] += $g;
            }
        }

        $fogli = (int) $fase->fogli_buoni;
        $g1000 = $fogli > 0 ? round($totGen / $fogli * 1000, 1) : 0;

        printf("%-14s %-18s %-8d %-8.1f %-8.1f %-10.1f %-10.1f %-8.1f\n",
            $commessa, $fase->fase, $fogli, $totGen,
            $totals['C'], $totals['M'], $totals['Y'], $totals['K']);
        if ($totals['Altro'] > 0) {
            printf("  (+ %.1f g inchiostri speciali)\n", $totals['Altro']);
        }
        if ($fogli > 0) {
            printf("  → %.2f g totale · %.2f g/1000fg\n", $totGen, $g1000);
        }
    } catch (\Exception $e) {
        printf("%-14s ERR: %s\n", $commessa, substr($e->getMessage(), 0, 80));
    }
}

echo "\n--- Note coerenza (formato 70x100 / 72x102) ---\n";
echo "Valori tipici stampa 4 colori offset:\n";
echo "  - Copertura bassa (testi): ~20-50 g/1000fg totale CMYK\n";
echo "  - Copertura media: ~50-120 g/1000fg\n";
echo "  - Copertura alta (immagini piene): ~150-250 g/1000fg\n";
echo "Se vedi valori >> 500 g/1000fg o << 10 g/1000fg → possibile anomalia\n";
