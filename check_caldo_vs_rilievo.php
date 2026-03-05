<?php
/**
 * Confronta commesse con STAMPACALDO e RILIEVO in Onda.
 * Mostra quali commesse hanno stampa a caldo ma NON rilievo.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$righe = DB::connection('onda')->select("
    SELECT p.CodCommessa, f.CodFase
    FROM PRDDocTeste p
    JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE f.CodFase IN ('STAMPACALDOJOH', 'RILIEVOASECCOJOH')
    ORDER BY p.CodCommessa, f.CodFase
");

$commesse = [];
foreach ($righe as $r) {
    $c = trim($r->CodCommessa);
    if (!isset($commesse[$c])) $commesse[$c] = [];
    $commesse[$c][] = $r->CodFase;
}

echo "Commesse con STAMPACALDO e/o RILIEVO:\n";
echo str_repeat('-', 70) . "\n";

$soloCaldo = 0;
$soloRilievo = 0;
$entrambe = 0;

foreach ($commesse as $c => $fasi) {
    $hasCaldo = in_array('STAMPACALDOJOH', $fasi);
    $hasRilievo = in_array('RILIEVOASECCOJOH', $fasi);

    if ($hasCaldo && $hasRilievo) {
        echo "  {$c} → ENTRAMBE\n";
        $entrambe++;
    } elseif ($hasCaldo) {
        echo "  {$c} → SOLO STAMPA A CALDO (manca rilievo)\n";
        $soloCaldo++;
    } else {
        echo "  {$c} → SOLO RILIEVO (manca stampa a caldo)\n";
        $soloRilievo++;
    }
}

echo "\nRiepilogo:\n";
echo "  Entrambe: {$entrambe}\n";
echo "  Solo stampa a caldo: {$soloCaldo}\n";
echo "  Solo rilievo: {$soloRilievo}\n";
echo "  Totale commesse: " . count($commesse) . "\n";
