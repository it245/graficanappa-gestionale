<?php
/**
 * Retrofit storico: aggiorna fogli_buoni/fogli_scarto per fasi stampa offset
 * già terminate/consegnate usando Prinect API worksteps.
 *
 * Uso:
 *   php retrofit_prinect_scarti.php          → dry-run (mostra cosa farebbe)
 *   php retrofit_prinect_scarti.php --apply  → applica modifiche
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Http\Services\PrinectService;

$apply = in_array('--apply', $argv);
$prinect = app(PrinectService::class);

$fasi = OrdineFase::with('ordine')
    ->whereIn('stato', [3, 4])
    ->where(function ($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    ->whereHas('ordine')
    ->get()
    ->groupBy(fn($f) => $f->ordine->commessa ?? '');

$aggiornati = 0; $skip = 0; $err = 0;
foreach ($fasi as $commessa => $gruppo) {
    $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
    if (!$jobId || !is_numeric($jobId)) { $skip++; continue; }

    try {
        $wsData = $prinect->getJobWorksteps($jobId);
        $worksteps = collect($wsData['worksteps'] ?? [])
            ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
            ->filter(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED'); // Solo completed (esclude WAITING con placeholder forecast)
        if ($worksteps->isEmpty()) { $skip++; continue; }

        $totBuoni = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);
        $totScarto = $worksteps->sum(fn($ws) => $ws['wasteProduced'] ?? 0);

        $wsValues = $worksteps->values();
        $fasiValues = $gruppo->values();
        $match11 = $fasiValues->count() > 1 && $fasiValues->count() === $wsValues->count();

        foreach ($fasiValues as $i => $fase) {
            if ($match11) {
                $ws = $wsValues[$i] ?? null;
                if (!$ws) continue;
                $newBuoni = $ws['amountProduced'] ?? 0;
                $newScarto = $ws['wasteProduced'] ?? 0;
            } else {
                $newBuoni = $totBuoni;
                $newScarto = $totScarto;
            }
            $oldBuoni = $fase->fogli_buoni ?? 0;
            $oldScarto = $fase->fogli_scarto ?? 0;

            if ($newBuoni > $oldBuoni || $newScarto > $oldScarto) {
                echo sprintf("%s fase=%s [%d] buoni %s→%s, scarto %s→%s%s\n",
                    $commessa, $fase->fase, $fase->id,
                    $oldBuoni, $newBuoni,
                    $oldScarto, $newScarto,
                    $apply ? ' [UPDATED]' : ' [dry-run]'
                );
                if ($apply) {
                    $fase->fogli_buoni = max($oldBuoni, $newBuoni);
                    $fase->fogli_scarto = max($oldScarto, $newScarto);
                    $fase->save();
                }
                $aggiornati++;
            }
        }
    } catch (\Exception $e) {
        echo "ERR {$commessa}: " . $e->getMessage() . "\n";
        $err++;
    }
}

echo "\nAggiornati: $aggiornati | Skip: $skip | Errori: $err\n";
echo $apply ? "Dati salvati.\n" : "DRY-RUN. Rilancia con --apply per applicare.\n";
