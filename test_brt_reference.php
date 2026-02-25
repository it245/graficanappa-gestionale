<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test BRT API - cerco per riferimento mittente (NumeroDocumento DDT)
$userID = '0673970';
$password = 'oks48xc6at';

// Riferimento mittente numerico e alfabetico dalla spedizione nota
$riferimentoAlfabetico = '473';
$riferimentoNumerico = '28733';

// 1. Provo endpoint con riferimento mittente alfanumerico
$endpoints = [
    "https://api.brt.it/rest/v1/tracking/riferimento/{$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/tracking/shipment/{$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/tracking/reference/{$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/shipments?reference={$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/tracking/rmn/{$riferimentoNumerico}",
    "https://api.brt.it/rest/v1/tracking/rma/{$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/tracking/senderReference/{$riferimentoAlfabetico}",
    "https://api.brt.it/rest/v1/shipments/history?customerID={$userID}",
];

foreach ($endpoints as $url) {
    echo "\n=== GET {$url} ===\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "userID: {$userID}",
            "password: {$password}",
            "Accept: application/json",
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  HTTP {$httpCode}\n";
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "  " . substr(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 500) . "\n";
        } else {
            echo "  " . substr($response, 0, 500) . "\n";
        }
    }
}

// 2. Provo anche con POST
echo "\n\n=== POST tests ===\n";
$postEndpoints = [
    "https://api.brt.it/rest/v1/tracking/search" => json_encode(['reference' => $riferimentoAlfabetico]),
    "https://api.brt.it/rest/v1/shipments/search" => json_encode(['senderReference' => $riferimentoAlfabetico, 'customerID' => $userID]),
];

foreach ($postEndpoints as $url => $body) {
    echo "\n=== POST {$url} ===\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            "userID: {$userID}",
            "password: {$password}",
            "Content-Type: application/json",
            "Accept: application/json",
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  HTTP {$httpCode}\n";
    if ($response) {
        echo "  " . substr($response, 0, 500) . "\n";
    }
}

// 3. Provo il parcelID noto per conferma che funziona ancora
echo "\n\n=== Verifica parcelID noto ===\n";
$url = "https://api.brt.it/rest/v1/tracking/parcelID/067138050411341";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "userID: {$userID}",
        "password: {$password}",
        "Accept: application/json",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  HTTP {$httpCode} - " . (json_decode($response) ? "OK funziona" : "errore") . "\n";
