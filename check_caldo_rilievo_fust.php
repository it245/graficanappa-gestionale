<?php
/**
 * Confronta commesse con STAMPACALDO, RILIEVO e FUSTBOBST in Onda.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$righe = DB::connection('onda')->select("
    SELECT p.CodCommessa, f.CodFase
    FROM PRDDocTeste p
    JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE f.CodFase LIKE 'STAMPACALDO%'
       OR f.CodFase LIKE 'RILIEVO%'
       OR f.CodFase LIKE 'FUSTBOB%'
    ORDER BY p.CodCommessa, f.CodFase
");

$commesse = [];
foreach ($righe as $r) {
    $c = trim($r->CodCommessa);
    if (!isset($commesse[$c])) $commesse[$c] = [];
    $commesse[$c][] = $r->CodFase;
}

// Filtra solo commesse che hanno almeno stampa a caldo O rilievo
echo "Commesse con STAMPACALDO e/o RILIEVO — check FUSTBOBST:\n";
echo str_repeat('-', 90) . "\n";

$stats = ['caldo+rilievo+fust' => 0, 'caldo+fust' => 0, 'caldo_solo' => 0, 'rilievo+fust' => 0, 'rilievo_solo' => 0];

foreach ($commesse as $c => $fasi) {
    $hasCaldo = collect($fasi)->contains(fn($f) => str_starts_with($f, 'STAMPACALDO'));
    $hasRilievo = collect($fasi)->contains(fn($f) => str_starts_with($f, 'RILIEVO'));
    $hasFust = collect($fasi)->contains(fn($f) => str_starts_with($f, 'FUSTBOB'));

    if (!$hasCaldo && !$hasRilievo) continue;

    $fasiStr = implode(', ', array_unique($fasi));

    if ($hasCaldo && $hasRilievo && $hasFust) {
        $stats['caldo+rilievo+fust']++;
    } elseif ($hasCaldo && $hasFust) {
        $stats['caldo+fust']++;
    } elseif ($hasCaldo) {
        $stats['caldo_solo']++;
    } elseif ($hasRilievo && $hasFust) {
        $stats['rilievo+fust']++;
    } else {
        $stats['rilievo_solo']++;
    }
}

echo "\nRiepilogo:\n";
echo "  Stampa a caldo + Rilievo + Fustella Bobst: {$stats['caldo+rilievo+fust']}\n";
echo "  Stampa a caldo + Fustella Bobst (no rilievo): {$stats['caldo+fust']}\n";
echo "  Stampa a caldo sola (no fustella, no rilievo): {$stats['caldo_solo']}\n";
echo "  Rilievo + Fustella Bobst (no caldo): {$stats['rilievo+fust']}\n";
echo "  Rilievo solo (no fustella, no caldo): {$stats['rilievo_solo']}\n";
