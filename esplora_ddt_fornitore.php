<?php
/**
 * Esplora DDT emesse a fornitore da Onda per capire il formato delle descrizioni.
 * Uso: php esplora_ddt_fornitore.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$righeDDT = DB::connection('onda')->select("
    SELECT t.IdDoc, t.DataDocumento, t.DataRegistrazione, t.IdAnagrafica,
           a.RagioneSociale, r.Descrizione, r.Qta, r.CodUnMis, r.NrRiga
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.TipoDocumento = 7
      AND t.DataRegistrazione >= DATEADD(day, -60, GETDATE())
    ORDER BY t.DataRegistrazione DESC, t.IdDoc, r.NrRiga
");

echo "=== DDT EMESSE A FORNITORE (ultimi 60 giorni) ===\n";
echo "Righe trovate: " . count($righeDDT) . "\n\n";

$perDoc = [];
foreach ($righeDDT as $r) {
    $perDoc[$r->IdDoc][] = $r;
}

foreach ($perDoc as $idDoc => $righe) {
    $primo = $righe[0];
    $data = $primo->DataDocumento ? date('d/m/Y', strtotime($primo->DataDocumento)) : '-';
    echo "--- DDT #{$idDoc} | {$data} | Fornitore: {$primo->RagioneSociale} ---\n";
    foreach ($righe as $r) {
        $desc = trim($r->Descrizione ?? '');
        echo "  Riga {$r->NrRiga}: {$desc} | Qta: {$r->Qta} {$r->CodUnMis}\n";
    }
    echo "\n";
}
