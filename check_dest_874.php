<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

// Query diretta per DDT 874
$result = DB::connection('onda')->selectOne("
    SELECT t.IdAnagrafica, c.IdIndirizzoMerce,
           dest.RagioneSociale AS DestNome, dest.Indirizzo AS DestIndirizzo,
           dest.Cap AS DestCap, dest.Citta AS DestCitta
    FROM ATTDocTeste t
    JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
    LEFT JOIN STDAnagIndirizzi dest ON dest.IdAnagrafica = t.IdAnagrafica
        AND dest.IdIndirizzo = c.IdIndirizzoMerce
    WHERE t.NumeroDocumento = '0000874'
      AND t.TipoDocumento = 3
      AND YEAR(t.DataDocumento) = YEAR(GETDATE())
");

echo "IdAnagrafica: " . ($result->IdAnagrafica ?? 'NULL') . "\n";
echo "IdIndirizzoMerce: " . ($result->IdIndirizzoMerce ?? 'NULL') . "\n";
echo "DestNome: [" . ($result->DestNome ?? 'NULL') . "]\n";
echo "DestIndirizzo: [" . ($result->DestIndirizzo ?? 'NULL') . "]\n";
echo "DestCitta: [" . ($result->DestCitta ?? 'NULL') . "]\n";

// Test diretto
echo "\n--- Test diretto STDAnagIndirizzi ---\n";
$addr = DB::connection('onda')->select("
    SELECT * FROM STDAnagIndirizzi WHERE IdAnagrafica = ? AND IdIndirizzo = ?
", [$result->IdAnagrafica ?? 0, $result->IdIndirizzoMerce ?? 0]);
foreach ($addr as $a) {
    echo "  " . json_encode($a, JSON_UNESCAPED_UNICODE) . "\n";
}
