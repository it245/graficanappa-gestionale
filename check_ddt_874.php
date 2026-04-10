<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

$coda = DB::connection('onda')->selectOne("
    SELECT c.RagSocSped, c.IndirizzoSped, c.CapSped, c.CittaSped, c.ProvSped,
           c.IdIndirizzoMerce, c.idIndirizzoFattura
    FROM ATTDocCoda c
    JOIN ATTDocTeste t ON c.IdDoc = t.IdDoc
    WHERE t.NumeroDocumento = '0000874'
      AND t.TipoDocumento = 3
      AND YEAR(t.DataDocumento) = YEAR(GETDATE())
");

echo "=== CODA DDT 874 ===\n";
if ($coda) {
    echo "  RagSocSped: [{$coda->RagSocSped}]\n";
    echo "  IndirizzoSped: [{$coda->IndirizzoSped}]\n";
    echo "  CapSped: [{$coda->CapSped}]\n";
    echo "  CittaSped: [{$coda->CittaSped}]\n";
    echo "  ProvSped: [{$coda->ProvSped}]\n";
    echo "  IdIndirizzoMerce: [{$coda->IdIndirizzoMerce}]\n";
    echo "  idIndirizzoFattura: [{$coda->idIndirizzoFattura}]\n";
} else {
    echo "  Coda non trovata!\n";
}

// Controlla anche DDT 875 (che ha la destinazione nell'esempio)
$coda875 = DB::connection('onda')->selectOne("
    SELECT c.RagSocSped, c.IndirizzoSped, c.CapSped, c.CittaSped, c.ProvSped,
           c.IdIndirizzoMerce
    FROM ATTDocCoda c
    JOIN ATTDocTeste t ON c.IdDoc = t.IdDoc
    WHERE t.NumeroDocumento = '0000875'
      AND t.TipoDocumento = 3
      AND YEAR(t.DataDocumento) = YEAR(GETDATE())
");

echo "\n=== CODA DDT 875 (ha destinazione) ===\n";
if ($coda875) {
    echo "  RagSocSped: [{$coda875->RagSocSped}]\n";
    echo "  IndirizzoSped: [{$coda875->IndirizzoSped}]\n";
    echo "  CittaSped: [{$coda875->CittaSped}]\n";
    echo "  IdIndirizzoMerce: [{$coda875->IdIndirizzoMerce}]\n";
}
