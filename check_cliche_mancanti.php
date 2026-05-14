<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Tutte commesse con almeno 1 fase stato 0 o 1 (caricato/pronto): no senso check terminate
$commesse = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.stato', ['0', '1'])
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa')
    ->distinct()
    ->orderBy('ordini.commessa')
    ->pluck('commessa');

echo "Commesse attive con fasi 0/1: " . count($commesse) . "\n\n";

$totMissing = 0;
$totOrdini = 0;
foreach ($commesse as $comm) {
    $ordini = DB::table('ordini')
        ->where('commessa', $comm)
        ->select('id', 'cod_art', 'descrizione', 'cliche_numero', 'cliche_match_type')
        ->get();
    $piEsiste = DB::table('ordine_fasi')
        ->whereIn('ordine_id', $ordini->pluck('id'))
        ->where('fase', 'PI01')
        ->whereNull('deleted_at')
        ->exists();
    if (!$piEsiste) continue;

    $missing = $ordini->filter(fn($o) => empty($o->cliche_numero));
    if ($missing->isEmpty()) continue;

    echo "$comm  ({$ordini->count()} ordini, {$missing->count()} senza cliché)\n";
    foreach ($missing as $o) {
        echo "  ordine {$o->id} desc=" . substr($o->descrizione ?? '-', 0, 90) . "\n";
        $totMissing++;
    }
    $totOrdini += $ordini->count();
}
echo "\nTOTALE ordini senza cliché: $totMissing\n";
