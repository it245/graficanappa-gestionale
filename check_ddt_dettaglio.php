<?php
// Mostra TUTTE righe di un DDT specifico Onda
// Uso: php check_ddt_dettaglio.php 134107
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$idDoc = $argv[1] ?? '134107';

echo "=== DDT IdDoc=$idDoc ===\n";

$testa = DB::connection('onda')->select("
    SELECT t.*, an.RagioneSociale
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche an ON t.IdAnagrafica = an.IdAnagrafica
    WHERE t.IdDoc = ?
", [$idDoc]);
if (empty($testa)) { echo "Non trovato\n"; exit; }
$t = $testa[0];
echo "Numero: {$t->NumeroDocumento} | Data: {$t->DataDocumento} | Tipo: {$t->TipoDocumento}\n";
echo "Anagrafica: {$t->RagioneSociale}\n";
echo "CodCommessa(testa): " . ($t->CodCommessa ?? '-') . "\n\n";

echo "--- TUTTE le righe ---\n";
$righe = DB::connection('onda')->select("
    SELECT NrRiga, CodArt, Descrizione, Qta, CodUnMis, TipoRiga, CodCommessa
    FROM ATTDocRighe
    WHERE IdDoc = ?
    ORDER BY NrRiga
", [$idDoc]);
foreach ($righe as $r) {
    $cm = $r->CodCommessa ?? '-';
    $desc = $r->Descrizione ?? '';
    echo "  [{$r->NrRiga}] cod={$r->CodArt} | qta={$r->Qta} {$r->CodUnMis} | TipoRiga={$r->TipoRiga} | comm={$cm}\n";
    echo "      DESC: " . str_replace("\n", " | ", $desc) . "\n";
}
