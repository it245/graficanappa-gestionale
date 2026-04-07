<?php
// Controlla commesse con STAMPA XL a stato 0/1: confronta per cod_art Onda vs MES
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONFRONTO STAMPA XL (stato 0/1): ONDA vs MES per cod_art ===\n\n";

// Commesse con almeno una STAMPA XL a stato 0 o 1
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', [0, 1])
    ->distinct()
    ->pluck('ordini.commessa');

echo "Commesse con STAMPA XL a stato 0/1: {$commesse->count()}\n\n";

$problemi = [];

foreach ($commesse as $commessa) {
    // Articoli con STAMPA XL106 su Onda
    $articoliOnda = DB::connection('onda')->select("
        SELECT p.CodArt, p.OC_Descrizione, f.QtaDaLavorare
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
          AND f.CodFase = 'STAMPA'
          AND f.CodMacchina LIKE '%XL106%'
        ORDER BY p.CodArt
    ", [$commessa]);

    $codArtOnda = array_map(fn($a) => $a->CodArt, $articoliOnda);

    // Articoli con STAMPA XL nel MES (tutti gli stati, non deleted)
    $fasiMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordini.cod_art', 'ordine_fasi.fase', 'ordine_fasi.qta_fase', 'ordine_fasi.stato')
        ->get();

    $codArtMes = $fasiMes->pluck('cod_art')->unique()->toArray();

    // Trova articoli su Onda che non hanno STAMPA nel MES
    $mancanti = array_diff($codArtOnda, $codArtMes);

    if (!empty($mancanti)) {
        $problemi[] = $commessa;
        echo "*** {$commessa} — STAMPA XL mancante per cod_art ***\n";
        echo "  Onda (" . count($codArtOnda) . " articoli con STAMPA XL):\n";
        foreach ($articoliOnda as $a) {
            $presente = in_array($a->CodArt, $codArtMes) ? '✓' : '✗ MANCANTE';
            echo "    {$presente} Art:{$a->CodArt} | Qta:{$a->QtaDaLavorare} | " . substr($a->OC_Descrizione, 0, 50) . "\n";
        }
        echo "  MES:\n";
        foreach ($fasiMes as $f) {
            echo "    Art:{$f->cod_art} | {$f->fase} | Qta:{$f->qta_fase} | stato:{$f->stato}\n";
        }
        echo "\n";
    }
}

echo "=== RIEPILOGO ===\n";
echo "Commesse con STAMPA mancante: " . count($problemi) . "\n";
foreach ($problemi as $p) {
    echo "  {$p}\n";
}
