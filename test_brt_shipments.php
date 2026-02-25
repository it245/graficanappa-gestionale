<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$userID = '0673970';
$password = 'oks48xc6at';

function brtRequest($method, $url, $userID, $password, $body = null) {
    $ch = curl_init($url);
    $headers = [
        "userID: {$userID}",
        "password: {$password}",
        "Accept: application/json",
        "Content-Type: application/json",
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($body) $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body);
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return compact('response', 'httpCode', 'error');
}

// 1. POST /shipments - potrebbe essere bollettazione o lista spedizioni
echo "=== 1. POST /shipments (vuoto) ===\n";
$r = brtRequest('POST', 'https://api.brt.it/rest/v1/shipments', $userID, $password, '{}');
echo "  HTTP {$r['httpCode']}\n";
if ($r['response']) echo "  " . substr($r['response'], 0, 1000) . "\n";

// 2. POST /shipments con riferimento
echo "\n=== 2. POST /shipments con riferimento ===\n";
$r = brtRequest('POST', 'https://api.brt.it/rest/v1/shipments', $userID, $password, [
    'senderReference' => '473',
    'customerID' => $userID,
]);
echo "  HTTP {$r['httpCode']}\n";
if ($r['response']) echo "  " . substr($r['response'], 0, 1000) . "\n";

// 3. Provo altri endpoint che potrebbero esistere
$tests = [
    ['GET',  '/rest/v1/tracking/ID/067138050411341'],
    ['GET',  '/rest/v1/tracking/legacyParcelID/067138050411341'],
    ['POST', '/rest/v1/tracking', ['parcelID' => '067138050411341']],
    ['GET',  '/rest/v1/shipments/067138050411341'],
    ['POST', '/rest/v1/shipments/search', ['senderReference' => '473']],
    ['POST', '/rest/v1/shipments/tracking', ['reference' => '473']],
    ['GET',  '/rest/v1/'],
    ['GET',  '/rest/v1/swagger.json'],
    ['GET',  '/rest/v1/openapi.json'],
    ['GET',  '/rest/v1/api-docs'],
    ['GET',  '/rest/'],
];

echo "\n=== 3. Scoperta altri endpoint ===\n";
foreach ($tests as [$method, $path, $body]) {
    $body = $body ?? null;
    $url = "https://api.brt.it{$path}";
    $r = brtRequest($method, $url, $userID, $password, $body);
    $label = "{$method} {$path}";
    echo "  {$label} -> HTTP {$r['httpCode']}";
    if ($r['httpCode'] != 404 && $r['httpCode'] != 0) {
        echo " ***";
        if ($r['response']) echo "\n    " . substr($r['response'], 0, 300);
    }
    echo "\n";
}

echo "\nFine test.\n";
