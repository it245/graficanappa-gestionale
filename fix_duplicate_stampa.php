<?php
/**
 * Trova e elimina fasi STAMPAXL106 duplicate sullo stesso ordine.
 * Tiene la fase con stato più alto (o id più basso a parità di stato).
 *
 * Uso: php fix_duplicate_stampa.php [--dry-run]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$dryRun = in_array('--dry-run', $argv);
if ($dryRun) echo "=== DRY RUN (nessuna modifica) ===\n\n";

// Trova ordini con più di 1 STAMPAXL106 della STESSA fase_catalogo
$duplicati = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
    ->select('ordine_fasi.ordine_id', 'ordine_fasi.fase_catalogo_id', 'fasi_catalogo.nome',
        DB::raw('COUNT(*) as cnt'))
    ->where('fasi_catalogo.nome', 'like', 'STAMPAXL106%')
    ->whereNull('ordine_fasi.deleted_at')
    ->groupBy('ordine_fasi.ordine_id', 'ordine_fasi.fase_catalogo_id', 'fasi_catalogo.nome')
    ->having('cnt', '>', 1)
    ->get();

if ($duplicati->isEmpty()) {
    echo "Nessun duplicato STAMPAXL106 trovato.\n";
    exit(0);
}

$totale = 0;
foreach ($duplicati as $dup) {
    $ordine = DB::table('ordini')->find($dup->ordine_id);
    echo "Commessa: {$ordine->commessa} | Ordine #{$dup->ordine_id} | {$dup->nome} x{$dup->cnt}\n";

    // Prendi tutte le fasi duplicate, ordina: stato DESC (tieni la più avanzata), poi id ASC
    $fasi = DB::table('ordine_fasi')
        ->where('ordine_id', $dup->ordine_id)
        ->where('fase_catalogo_id', $dup->fase_catalogo_id)
        ->whereNull('deleted_at')
        ->orderByDesc('stato')
        ->orderBy('id')
        ->get();

    $keeper = $fasi->first();
    echo "  TENGO: Fase #{$keeper->id} stato:{$keeper->stato}\n";

    foreach ($fasi->slice(1) as $doppione) {
        echo "  ELIMINO: Fase #{$doppione->id} stato:{$doppione->stato}\n";
        if (!$dryRun) {
            DB::table('ordine_fasi')->where('id', $doppione->id)->update([
                'deleted_at' => now(),
            ]);
        }
        $totale++;
    }
}

echo "\nTotale eliminati: {$totale}" . ($dryRun ? " (dry-run)" : "") . "\n";
