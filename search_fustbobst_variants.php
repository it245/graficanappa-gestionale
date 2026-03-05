<?php
/**
 * Cerca tutte le varianti FUSTBOBST in Onda.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$righe = DB::connection('onda')->select(
    "SELECT DISTINCT f.CodFase, COUNT(*) as cnt FROM PRDDocFasi f WHERE f.CodFase LIKE 'FUSTBOB%' GROUP BY f.CodFase ORDER BY f.CodFase"
);

echo "Varianti FUSTBOBST in Onda:\n";
foreach ($righe as $r) {
    echo "  {$r->CodFase} ({$r->cnt} occorrenze)\n";
}

// Ora conta commesse con STAMPACALDO + FUSTBOBSTRILIEVI
echo "\n--- Commesse con STAMPACALDO + FUSTBOBST*RILIEVI ---\n";
$righe2 = DB::connection('onda')->select("
    SELECT p.CodCommessa, f.CodFase
    FROM PRDDocTeste p
    JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE f.CodFase LIKE 'STAMPACALDO%'
       OR f.CodFase LIKE 'FUSTBOB%RILIEVI%'
       OR f.CodFase LIKE 'FUSTBOB%RILIEV%'
    ORDER BY p.CodCommessa, f.CodFase
");

$commesse = [];
foreach ($righe2 as $r) {
    $c = trim($r->CodCommessa);
    if (!isset($commesse[$c])) $commesse[$c] = [];
    $commesse[$c][trim($r->CodFase)] = true;
}

$conEntrambe = 0;
$soloFustRilievi = 0;
foreach ($commesse as $c => $fasi) {
    $hasCaldo = collect(array_keys($fasi))->contains(fn($f) => str_starts_with($f, 'STAMPACALDO'));
    $hasFustRilievi = collect(array_keys($fasi))->contains(fn($f) => str_starts_with($f, 'FUSTBOB') && stripos($f, 'RILIEV') !== false);

    if ($hasCaldo && $hasFustRilievi) {
        echo "  {$c} → CALDO + FUSTBOBST-RILIEVI: " . implode(', ', array_keys($fasi)) . "\n";
        $conEntrambe++;
    } elseif ($hasFustRilievi) {
        $soloFustRilievi++;
    }
}

echo "\nCaldo + FustBobst Rilievi: {$conEntrambe}\n";
echo "Solo FustBobst Rilievi (no caldo): {$soloFustRilievi}\n";
