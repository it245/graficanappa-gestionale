<?php
/**
 * Debug: prova a cercare DDT 507-509 usando il rif_num (IdDoc Onda o NumeroDocumento numerico)
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$brt = new \App\Http\Services\BrtService();
$clienteId = config('services.brt.user_id');

// Prima: scopriamo cosa c'è su Onda per i DDT 499, 507, 508, 509
echo "=== IdDoc Onda per DDT di Ferrovie dello Stato ===\n\n";

$ddtOnda = DB::connection('onda')->select("
    SELECT DISTINCT t.IdDoc, t.NumeroDocumento, t.DataDocumento
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    WHERE t.TipoDocumento = 3
      AND r.CodCommessa = '0066573-26'
      AND r.TipoRiga = 1
    ORDER BY t.NumeroDocumento
");

foreach ($ddtOnda as $d) {
    $num = ltrim($d->NumeroDocumento, '0');
    echo "  DDT {$num} (NumDoc: {$d->NumeroDocumento}) → IdDoc: {$d->IdDoc} | Data: {$d->DataDocumento}\n";
}

// Ora vediamo: per DDT 499 il rif_num era 28783. Corrisponde al suo IdDoc?
echo "\n=== Verifica: DDT 499 ha rif_num=28783. Il suo IdDoc è... ===\n";
$ddt499 = DB::connection('onda')->selectOne("
    SELECT t.IdDoc FROM ATTDocTeste t WHERE t.TipoDocumento = 3 AND t.NumeroDocumento = '0000499'
    AND t.DataRegistrazione >= '2026-01-01'
");
echo "  IdDoc DDT 499 (2026): " . ($ddt499->IdDoc ?? 'NON TROVATO') . "\n";

// Proviamo a cercare su BRT usando gli IdDoc come rif_num
echo "\n=== Ricerca BRT con IdDoc come RIFERIMENTO_MITTENTE_NUMERICO ===\n\n";

$ctx = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['timeout' => 30],
]);
$wsdlUrl = 'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMAService/GetIdSpedizioneByRMA?wsdl';
$wsdl = file_get_contents($wsdlUrl, false, $ctx);
$wsdl = str_replace('http://wsr.brt.it', 'https://wsr.brt.it', $wsdl);
$tmp = tempnam(sys_get_temp_dir(), 'brt_') . '.xml';
file_put_contents($tmp, $wsdl);

$soap = new SoapClient($tmp, [
    'trace' => true, 'exceptions' => true,
    'connection_timeout' => 30, 'stream_context' => $ctx,
    'cache_wsdl' => WSDL_CACHE_NONE,
]);

foreach ($ddtOnda as $d) {
    $num = ltrim($d->NumeroDocumento, '0');
    $idDoc = $d->IdDoc;
    echo "  DDT {$num} (IdDoc {$idDoc}): ";

    // Prova con IdDoc come rif numerico
    try {
        $result = $soap->getidspedizionebyrma(['arg0' => [
            'CLIENTE_ID' => $clienteId,
            'RIFERIMENTO_MITTENTE_NUMERICO' => (string) $idDoc,
        ]]);
        $esito = $result->return->ESITO ?? -1;
        $spedId = $result->return->SPEDIZIONE_ID ?? 0;
        echo "NUM(IdDoc) esito={$esito}, spedId={$spedId}";

        if ($esito == 0 && $spedId) {
            $tracking = $brt->getTrackingBySpedizioneId((string) $spedId);
            echo " → " . ($tracking['stato'] ?? '?');
        }
    } catch (\Exception $e) {
        echo "ERRORE";
    }
    echo "\n";
}

echo "\nScript completato.\n";
