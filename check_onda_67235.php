<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$comm = '67235';

$prd = DB::connection('onda')->select(
    "SELECT p.IdDoc, p.CodArt, p.CodCommessa, f.CodFase, f.QtaDaLavorare
     FROM PRDDocTeste p
     LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
     WHERE p.CodCommessa = ?
     ORDER BY p.IdDoc, f.CodFase",
    [$comm]
);

echo "\n=== PRD Onda commessa $comm ===\n";
echo "Totale righe: " . count($prd) . "\n";
foreach ($prd as $r) {
    echo " IdDoc={$r->IdDoc} CodArt={$r->CodArt} CodFase=" . ($r->CodFase ?? '-') . " QtaDaLavorare=" . ($r->QtaDaLavorare ?? '-') . "\n";
}

echo "\n=== Descrizioni ATTDocRighe ===\n";
$desc = DB::connection('onda')->select(
    "SELECT r.IdDoc, r.NrRiga, r.TipoRiga, r.CodArt, r.Descrizione
     FROM ATTDocRighe r
     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
     WHERE t.CodCommessa = ?
     ORDER BY r.NrRiga",
    [$comm]
);
foreach ($desc as $r) {
    echo " IdDoc={$r->IdDoc} TipoRiga={$r->TipoRiga} CodArt={$r->CodArt}: " . substr($r->Descrizione ?? '', 0, 100) . "\n";
}
