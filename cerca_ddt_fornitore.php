<?php
// Uso: php cerca_ddt_fornitore.php 66845
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66845';

echo "=== Cerca DDT/documenti con '{$cerca}' su Onda ===\n\n";

// 1. Documenti con commessa che contiene il numero
echo "--- Documenti per commessa ---\n";
$docs = DB::connection('onda')->select("
    SELECT TOP 20 t.TipoDocumento, t.NumeroDocumento, t.DataRegistrazione, t.CodCommessa,
           a.RagioneSociale, t.StatoDocumento
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.CodCommessa LIKE ?
    ORDER BY t.DataRegistrazione DESC
", ['%' . $cerca . '%']);

foreach ($docs as $d) {
    echo "  Tipo:{$d->TipoDocumento} | Num:{$d->NumeroDocumento} | Comm:{$d->CodCommessa} | Data:{$d->DataRegistrazione} | {$d->RagioneSociale} | Stato:{$d->StatoDocumento}\n";
}
if (empty($docs)) echo "  Nessuno\n";

// 2. Righe documenti con il numero nella descrizione
echo "\n--- Righe con '{$cerca}' nella descrizione ---\n";
$righe = DB::connection('onda')->select("
    SELECT TOP 20 t.TipoDocumento, t.NumeroDocumento, t.CodCommessa, t.DataRegistrazione,
           a.RagioneSociale, r.Descrizione, r.CodArt
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE r.Descrizione LIKE ?
    ORDER BY t.DataRegistrazione DESC
", ['%' . $cerca . '%']);

foreach ($righe as $r) {
    echo "  Tipo:{$r->TipoDocumento} | Num:{$r->NumeroDocumento} | Comm:{$r->CodCommessa} | {$r->RagioneSociale} | Art:{$r->CodArt} | Desc:" . substr($r->Descrizione, 0, 120) . "\n";
}
if (empty($righe)) echo "  Nessuno\n";

// 3. Tutti i tipi documento nel sistema
echo "\n--- Tipi documento disponibili ---\n";
$tipi = DB::connection('onda')->select("
    SELECT TipoDocumento, COUNT(*) as cnt
    FROM ATTDocTeste
    GROUP BY TipoDocumento
    ORDER BY TipoDocumento
");
foreach ($tipi as $t) {
    echo "  Tipo {$t->TipoDocumento}: {$t->cnt} documenti\n";
}

echo "\nDONE\n";
