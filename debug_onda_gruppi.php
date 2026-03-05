<?php
/**
 * Debug: mostra come il sync vede i gruppi Onda per una commessa.
 * Uso: php debug_onda_gruppi.php 0066648
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessaArg = $argv[1] ?? '0066648';

$righeOnda = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        o.Descrizione AS OC_Descrizione,
        f.CodFase,
        f.CodMacchina,
        f.QtaDaLavorare,
        f.CodUnMis
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    LEFT JOIN ATTDocRighe o ON t.IdDoc = o.IdDoc AND p.CodArt = o.CodArticolo
    WHERE t.CodCommessa LIKE ?
    ORDER BY t.CodCommessa, p.CodArt, o.Descrizione, f.CodFase
", [$commessaArg . '%']);

echo "Righe Onda per commessa '{$commessaArg}': " . count($righeOnda) . "\n\n";

// Raggruppa come fa il sync
$gruppi = collect($righeOnda)->groupBy(function ($riga) {
    return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $riga->OC_Descrizione;
});

echo "Gruppi (CodCommessa|CodArt|Descrizione): " . $gruppi->count() . "\n";
echo str_repeat('=', 120) . "\n\n";

foreach ($gruppi as $chiave => $righe) {
    $prima = $righe->first();
    echo "GRUPPO: {$chiave}\n";
    echo "  Commessa: {$prima->CodCommessa} | Art: {$prima->CodArt}\n";
    echo "  Desc: " . substr($prima->OC_Descrizione ?? '-', 0, 80) . "\n";
    echo "  Fasi (" . count($righe) . "):\n";

    $fasiViste = [];
    foreach ($righe as $r) {
        $faseNome = trim($r->CodFase ?? '');
        $chiaveFase = $faseNome . '|' . ($r->QtaDaLavorare ?? 0);
        $dup = isset($fasiViste[$chiaveFase]) ? ' [SKIP-dedup]' : '';
        $fasiViste[$chiaveFase] = true;
        echo "    {$faseNome} | macchina: {$r->CodMacchina} | qta: {$r->QtaDaLavorare} {$r->CodUnMis}{$dup}\n";
    }
    echo "\n";
}
