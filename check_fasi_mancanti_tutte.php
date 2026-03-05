<?php
/**
 * Controlla TUTTE le commesse nel MES: per ciascuna confronta le fasi
 * presenti nel MES con quelle in Onda e segnala le mancanti.
 *
 * Uso: php check_fasi_mancanti_tutte.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Tutte le commesse attive nel MES (con almeno 1 fase non consegnata)
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

echo "Commesse attive nel MES: " . $commesse->count() . "\n";
echo str_repeat('=', 80) . "\n\n";

$problemi = 0;

foreach ($commesse as $commessa) {
    // Fasi nel MES (non soft-deleted)
    $fasiMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->leftJoin('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
        ->where('ordini.commessa', $commessa)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('fasi_catalogo.nome as fase_nome')
        ->pluck('fase_nome')
        ->filter()
        ->unique()
        ->values();

    // Fasi in Onda
    $fasiOnda = collect(DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
        ORDER BY f.CodFase
    ", [$commessa]))->pluck('CodFase');

    if ($fasiOnda->isEmpty()) continue;

    // Trova mancanti
    $mancanti = $fasiOnda->filter(function ($faseOnda) use ($fasiMes) {
        return !$fasiMes->contains($faseOnda);
    });

    if ($mancanti->isNotEmpty()) {
        $problemi++;
        echo "COMMESSA: {$commessa}\n";
        echo "  MES:     " . $fasiMes->implode(', ') . "\n";
        echo "  ONDA:    " . $fasiOnda->implode(', ') . "\n";
        echo "  MANCA:   " . $mancanti->implode(', ') . "\n\n";
    }
}

echo str_repeat('=', 80) . "\n";
echo "Commesse con fasi mancanti: {$problemi} / " . $commesse->count() . "\n";
