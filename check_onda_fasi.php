<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066648';

$righe = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.CodCommessa LIKE ?
    ORDER BY f.CodFase
", [$commessa . '%']);

echo "Fasi Onda per commessa {$commessa}:\n";
echo str_pad('CodFase', 30) . ' | ' . str_pad('CodMacchina', 20) . ' | Qta' . "\n";
echo str_repeat('-', 70) . "\n";
foreach ($righe as $r) {
    echo str_pad($r->CodFase ?? '-', 30) . ' | ' . str_pad($r->CodMacchina ?? '-', 20) . ' | ' . ($r->QtaDaLavorare ?? 0) . ' ' . ($r->CodUnMis ?? '') . "\n";
}
echo "\nTotale righe: " . count($righe) . "\n";
