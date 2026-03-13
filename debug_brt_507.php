<?php
/**
 * Debug: prova diverse strategie di ricerca BRT per DDT 507
 * Uso: php debug_brt_507.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$brt = new \App\Http\Services\BrtService();

// Varianti di riferimento da provare
$varianti = [
    '507',
    '0000507',
    '507/26',
    '507-26',
    '507/2026',
    'DDT507',
    'DDT 507',
    '0066573-26',  // commessa Ferrovie dello Stato
];

echo "=== DEBUG BRT - Ricerca DDT 507 ===\n\n";

// Accedi al metodo SOAP direttamente per provare più combinazioni
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
    'trace' => true,
    'exceptions' => true,
    'connection_timeout' => 30,
    'stream_context' => $ctx,
    'cache_wsdl' => WSDL_CACHE_NONE,
]);

$clienteId = config('services.brt.user_id');
echo "Cliente ID BRT: {$clienteId}\n\n";

foreach ($varianti as $rif) {
    echo "--- Provo riferimento: '{$rif}' ---\n";

    // Prova ALFA
    try {
        $result = $soap->getidspedizionebyrma(['arg0' => [
            'CLIENTE_ID' => $clienteId,
            'RIFERIMENTO_MITTENTE_ALFABETICO' => $rif,
        ]]);
        $esito = $result->return->ESITO ?? -1;
        $spedId = $result->return->SPEDIZIONE_ID ?? 'N/A';
        echo "  ALFA: esito={$esito}, spedizione_id={$spedId}\n";
    } catch (\Exception $e) {
        echo "  ALFA: ERRORE - " . substr($e->getMessage(), 0, 80) . "\n";
    }

    // Prova NUMERICO
    try {
        $result = $soap->getidspedizionebyrma(['arg0' => [
            'CLIENTE_ID' => $clienteId,
            'RIFERIMENTO_MITTENTE_NUMERICO' => $rif,
        ]]);
        $esito = $result->return->ESITO ?? -1;
        $spedId = $result->return->SPEDIZIONE_ID ?? 'N/A';
        echo "  NUM:  esito={$esito}, spedizione_id={$spedId}\n";
    } catch (\Exception $e) {
        echo "  NUM:  ERRORE - " . substr($e->getMessage(), 0, 80) . "\n";
    }

    echo "\n";
}

// Prova anche DDT 499 (che funziona) per confronto
echo "=== CONFRONTO: DDT 499 (funzionante) ===\n";
try {
    $result = $soap->getidspedizionebyrma(['arg0' => [
        'CLIENTE_ID' => $clienteId,
        'RIFERIMENTO_MITTENTE_ALFABETICO' => '499',
    ]]);
    $esito = $result->return->ESITO ?? -1;
    $spedId = $result->return->SPEDIZIONE_ID ?? 'N/A';
    echo "  499 ALFA: esito={$esito}, spedizione_id={$spedId}\n";

    // Se trovato, mostra il tracking completo
    if ($esito == 0 && $spedId && $spedId != 'N/A') {
        $data = $brt->getTrackingBySpedizioneId($spedId);
        if ($data && isset($data['bolla'])) {
            echo "  Rif alfa: " . ($data['bolla']['rif_mittente_alfa'] ?? '-') . "\n";
            echo "  Rif num:  " . ($data['bolla']['rif_mittente_num'] ?? '-') . "\n";
            echo "  Stato: " . ($data['stato'] ?? '-') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  499: ERRORE - " . $e->getMessage() . "\n";
}

// Prova 503 e 504 (che da Onda risultano BRT)
echo "\n=== DDT 503, 504, 505, 507, 508, 509 con ANNO 2026 ===\n";
foreach ([503, 504, 505, 507, 508, 509] as $ddt) {
    // Senza anno
    try {
        $result = $soap->getidspedizionebyrma(['arg0' => [
            'CLIENTE_ID' => $clienteId,
            'RIFERIMENTO_MITTENTE_ALFABETICO' => (string)$ddt,
        ]]);
        $esito = $result->return->ESITO ?? -1;
        $spedId = $result->return->SPEDIZIONE_ID ?? 'N/A';
        echo "  DDT {$ddt} senza anno: esito={$esito}, spedizione_id={$spedId}\n";
    } catch (\Exception $e) {
        echo "  DDT {$ddt} senza anno: ERRORE\n";
    }

    // Con anno 2026
    try {
        $result = $soap->getidspedizionebyrma(['arg0' => [
            'CLIENTE_ID' => $clienteId,
            'RIFERIMENTO_MITTENTE_ALFABETICO' => (string)$ddt,
            'SPEDIZIONE_ANNO' => 2026,
        ]]);
        $esito = $result->return->ESITO ?? -1;
        $spedId = $result->return->SPEDIZIONE_ID ?? 'N/A';
        echo "  DDT {$ddt} anno 2026: esito={$esito}, spedizione_id={$spedId}";
        if ($esito == 0 && $spedId) {
            $tracking = $brt->getTrackingBySpedizioneId($spedId);
            echo " → " . ($tracking['stato'] ?? '?');
        }
        echo "\n";
    } catch (\Exception $e) {
        echo "  DDT {$ddt} anno 2026: ERRORE\n";
    }
}

echo "\nScript completato.\n";
