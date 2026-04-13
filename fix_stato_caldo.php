<?php
/**
 * Fix una tantum: mette a stato 1 (pronto) le fasi stampa a caldo
 * che hanno la stampa offset terminata l'11/04/2026.
 *
 * Eseguire sul server: php fix_stato_caldo.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\OrdineFase;

$fasiCaldo = OrdineFase::where('stato', 0)
    ->whereHas('faseCatalogo', fn($q) => $q->whereHas('reparto', fn($r) => $r->where('nome', 'stampa a caldo')))
    ->with('ordine')
    ->get();

echo "Fasi stampa a caldo a stato 0: " . $fasiCaldo->count() . "\n\n";

$count = 0;
foreach ($fasiCaldo as $fase) {
    // Cerca stampa offset terminata l'11/04/2026
    $stampaTerminata = OrdineFase::where('ordine_id', $fase->ordine_id)
        ->whereHas('faseCatalogo', fn($q) => $q->whereHas('reparto', fn($r) => $r->whereIn('nome', ['stampa offset', 'digitale'])))
        ->where('stato', '>=', 3)
        ->whereDate('data_fine', '2026-04-11')
        ->exists();

    if ($stampaTerminata) {
        $fase->stato = 1;
        $fase->save();
        $count++;
        echo "  " . ($fase->ordine->commessa ?? '?') . " - " . $fase->fase . " -> stato 1\n";
    }
}

echo "\nAggiornate: $count fasi\n";
