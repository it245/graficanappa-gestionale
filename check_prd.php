<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '67296';

echo "=== PRDDocTeste (commesse produzione) ===\n";
$prd = DB::connection('onda')->select(
    "SELECT TOP 10 CodCommessa, IdDoc, DataDocumento FROM PRDDocTeste WHERE CodCommessa LIKE ?",
    ["%$cerca%"]
);
if (empty($prd)) {
    echo "Nessun PRDDocTeste per '$cerca' (commessa esiste ma SENZA ordine produzione)\n";
} else {
    foreach ($prd as $r) echo "  {$r->CodCommessa} | IdDoc={$r->IdDoc} | {$r->DataDocumento}\n";
}

echo "\n=== ATTDocTeste (commesse cliente) ===\n";
$att = DB::connection('onda')->select(
    "SELECT TOP 10 CodCommessa, IdDoc, TipoDocumento, DataRegistrazione FROM ATTDocTeste WHERE CodCommessa LIKE ?",
    ["%$cerca%"]
);
foreach ($att as $r) echo "  {$r->CodCommessa} | IdDoc={$r->IdDoc} | Tipo={$r->TipoDocumento} | {$r->DataRegistrazione}\n";

echo "\n=== PRDDocFasi (fasi produzione) ===\n";
$fasi = DB::connection('onda')->select(
    "SELECT TOP 20 f.CodFase, f.CodMacchina, f.QtaDaLavorare, p.CodCommessa
     FROM PRDDocFasi f
     INNER JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
     WHERE p.CodCommessa LIKE ?",
    ["%$cerca%"]
);
if (empty($fasi)) echo "Nessuna fase produzione\n";
else foreach ($fasi as $r) echo "  {$r->CodCommessa} | {$r->CodFase} | macc={$r->CodMacchina} | qta={$r->QtaDaLavorare}\n";
