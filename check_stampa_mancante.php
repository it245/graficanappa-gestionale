<?php
// Controlla tutte le commesse con STAMPA XL: confronta quante ne ha Onda vs MES
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONFRONTO STAMPA XL: ONDA vs MES ===\n\n";

// Prendi tutte le commesse attive dal MES che hanno almeno una STAMPA XL
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
    ->whereNull('ordine_fasi.deleted_at')
    ->whereRaw("ordine_fasi.stato REGEXP '^[0-9]+$' AND ordine_fasi.stato < 4")
    ->distinct()
    ->pluck('ordini.commessa');

echo "Commesse attive con STAMPA XL nel MES: {$commesse->count()}\n\n";

$problemi = [];

foreach ($commesse as $commessa) {
    // Conta STAMPA su Onda (per PrdIdDoc diversi)
    $stampeOnda = DB::connection('onda')->select("
        SELECT COUNT(DISTINCT p.IdDoc) as cnt
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
          AND f.CodFase = 'STAMPA'
          AND f.CodMacchina LIKE '%XL106%'
    ", [$commessa]);
    $countOnda = $stampeOnda[0]->cnt ?? 0;

    // Conta STAMPA XL nel MES (non deleted)
    $countMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
        ->whereNull('ordine_fasi.deleted_at')
        ->count();

    if ($countOnda != $countMes && $countOnda > 0) {
        // Dettaglio articoli Onda
        $articoli = DB::connection('onda')->select("
            SELECT p.CodArt, p.OC_Descrizione, f.QtaDaLavorare
            FROM PRDDocTeste p
            JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            WHERE p.CodCommessa = ?
              AND f.CodFase = 'STAMPA'
              AND f.CodMacchina LIKE '%XL106%'
            ORDER BY p.CodArt
        ", [$commessa]);

        // Dettaglio MES
        $fasiMes = DB::table('ordine_fasi')
            ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where('ordini.commessa', $commessa)
            ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
            ->whereNull('ordine_fasi.deleted_at')
            ->select('ordini.cod_art', 'ordine_fasi.fase', 'ordine_fasi.qta_fase', 'ordine_fasi.stato')
            ->get();

        $problemi[] = $commessa;
        echo "*** {$commessa} — Onda: {$countOnda} STAMPA, MES: {$countMes} STAMPA ***\n";
        echo "  Onda:\n";
        foreach ($articoli as $a) {
            echo "    Art:{$a->CodArt} | Qta:{$a->QtaDaLavorare} | " . substr($a->OC_Descrizione, 0, 50) . "\n";
        }
        echo "  MES:\n";
        foreach ($fasiMes as $f) {
            echo "    Art:{$f->cod_art} | {$f->fase} | Qta:{$f->qta_fase} | stato:{$f->stato}\n";
        }
        echo "\n";
    }
}

echo "=== RIEPILOGO ===\n";
echo "Commesse con discrepanza: " . count($problemi) . "\n";
foreach ($problemi as $p) {
    echo "  {$p}\n";
}
