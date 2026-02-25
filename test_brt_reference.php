<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$userID = '0673970';
$password = 'oks48xc6at';

$riferimentoAlfabetico = '473';
$riferimentoNumerico = '28733';

function brtGet($url, $userID, $password) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "userID: {$userID}",
            "password: {$password}",
            "Accept: application/json",
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_VERBOSE => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    return compact('response', 'httpCode', 'error', 'errno');
}

// 1. Test base: parcelID noto (deve funzionare)
echo "=== TEST BASE: parcelID noto ===\n";
$r = brtGet("https://api.brt.it/rest/v1/tracking/parcelID/067138050411341", $userID, $password);
echo "  HTTP {$r['httpCode']}";
if ($r['error']) echo " | ERRORE CURL [{$r['errno']}]: {$r['error']}";
if ($r['response']) {
    $data = json_decode($r['response'], true);
    echo $data ? " | OK - tracking trovato" : " | risposta non JSON";
}
echo "\n";

// Se il test base fallisce, il problema e' la connessione
if ($r['httpCode'] == 0) {
    echo "\n*** CONNESSIONE FALLITA - il server non raggiunge api.brt.it ***\n";
    echo "Possibili cause: firewall, proxy, DNS, SSL\n";
    echo "\nProvo test DNS e connettivita'...\n";

    // Test DNS
    $ip = gethostbyname('api.brt.it');
    echo "  DNS api.brt.it -> {$ip}\n";

    // Test con file_get_contents
    echo "\nProvo con file_get_contents...\n";
    $ctx = stream_context_create([
        'http' => [
            'header' => "userID: {$userID}\r\npassword: {$password}\r\nAccept: application/json\r\n",
            'timeout' => 15,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $resp = @file_get_contents("https://api.brt.it/rest/v1/tracking/parcelID/067138050411341", false, $ctx);
    echo "  Risultato: " . ($resp ? "OK (" . strlen($resp) . " bytes)" : "FALLITO - " . error_get_last()['message'] ?? 'sconosciuto') . "\n";

    exit(1);
}

// 2. Se connessione OK, provo endpoint con riferimento
echo "\n=== RICERCA PER RIFERIMENTO MITTENTE ===\n";
$endpoints = [
    "tracking/riferimento/{$riferimentoAlfabetico}",
    "tracking/shipment/{$riferimentoAlfabetico}",
    "tracking/reference/{$riferimentoAlfabetico}",
    "tracking/rmn/{$riferimentoNumerico}",
    "tracking/rma/{$riferimentoAlfabetico}",
    "tracking/senderReference/{$riferimentoAlfabetico}",
    "shipments?reference={$riferimentoAlfabetico}",
    "shipments/history?customerID={$userID}",
];

foreach ($endpoints as $ep) {
    $url = "https://api.brt.it/rest/v1/{$ep}";
    $r = brtGet($url, $userID, $password);
    $status = "HTTP {$r['httpCode']}";
    if ($r['httpCode'] == 200) {
        $data = json_decode($r['response'], true);
        $status .= " *** TROVATO! ***";
        echo "  {$ep} -> {$status}\n";
        echo "  " . substr(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 800) . "\n";
    } else {
        echo "  {$ep} -> {$status}\n";
    }
}

echo "\nFine test.\n";
