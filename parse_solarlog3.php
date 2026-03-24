<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$portalUrl = 'https://solarlog-portal.it';
$user = 'vittorio81@katamail.com';
$pass = 'nappa2017';

$jar = new \GuzzleHttp\Cookie\CookieJar();
$client = new \GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'timeout' => 15]);

// Login
$client->post("{$portalUrl}/974821.html", [
    'form_params' => ['username' => $user, 'password' => $pass, 'action' => 'login'],
]);

// Prendi sessid dal cookie
$sessid = '';
foreach ($jar->toArray() as $c) {
    if ($c['Name'] === 'SDS-Portal-V2') $sessid = $c['Value'];
}
echo "Session ID: {$sessid}" . PHP_EOL . PHP_EOL;

// Cerca nel JS compresso le chiamate RPC specifiche per yield/power
$jsResp = $client->get("{$portalUrl}/sds/template/default/javascript/compressed.js?v=3.5.15-24");
$js = (string)$jsResp->getBody();

// Cerca pattern RPC: method, action, module
echo "=== RPC METHODS NEL JS ===" . PHP_EOL;
if (preg_match_all('/["\']method["\']\s*:\s*["\']([^"\']+)/i', $js, $m)) {
    $methods = array_unique($m[1]);
    foreach ($methods as $met) echo "  method: {$met}" . PHP_EOL;
}
if (preg_match_all('/["\']action["\']\s*:\s*["\']([^"\']+)/i', $js, $m2)) {
    $actions = array_unique($m2[1]);
    foreach (array_slice($actions, 0, 20) as $a) echo "  action: {$a}" . PHP_EOL;
}

// Cerca specificamente YieldOverview, plant, dashboard RPC
echo PHP_EOL . "=== YIELD/PLANT RPC ===" . PHP_EOL;
if (preg_match_all('/(?:Yield|Plant|Dashboard|Current|Power|Energy)[^"\']{0,50}(?:method|action|rpc)[^"\']{0,100}/i', $js, $m3)) {
    foreach (array_unique(array_slice($m3[0], 0, 15)) as $match) {
        echo "  " . substr($match, 0, 120) . PHP_EOL;
    }
}

// Prova chiamate RPC comuni
echo PHP_EOL . "=== PROVA RPC ===" . PHP_EOL;
$rpcUrl = "{$portalUrl}/sds/rpc.php?sessid={$sessid}";

$calls = [
    ['method' => 'YieldOverviewPlant', 'params' => ['plantId' => 6808]],
    ['method' => 'getDashboard', 'params' => ['plantId' => 6808]],
    ['method' => 'getCurrentData', 'params' => ['plantId' => 6808]],
    ['method' => 'getYieldData', 'params' => ['plantId' => 6808]],
    ['method' => 'getPlantData', 'params' => ['plantId' => 6808]],
];

foreach ($calls as $call) {
    try {
        // Prova come JSON-RPC
        $r = $client->post($rpcUrl, [
            'json' => ['method' => $call['method'], 'params' => $call['params'], 'id' => 1],
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ]);
        $b = (string)$r->getBody();
        echo "  {$call['method']} (JSON-RPC) → {$r->getStatusCode()} (" . strlen($b) . " byte)" . PHP_EOL;
        if (strlen($b) < 2000) echo "    → {$b}" . PHP_EOL;
    } catch (\Exception $e) {}

    try {
        // Prova come form POST
        $r2 = $client->post($rpcUrl, [
            'form_params' => array_merge(['method' => $call['method']], $call['params']),
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ]);
        $b2 = (string)$r2->getBody();
        echo "  {$call['method']} (FORM) → {$r2->getStatusCode()} (" . strlen($b2) . " byte)" . PHP_EOL;
        if (strlen($b2) < 2000) echo "    → {$b2}" . PHP_EOL;
    } catch (\Exception $e) {}
}

// Prova anche la pagina yield con AJAX header
echo PHP_EOL . "=== YIELD PAGE CON AJAX HEADER ===" . PHP_EOL;
try {
    $r = $client->get("{$portalUrl}/emulated_yieldov_3650.html", [
        'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
    ]);
    $b = (string)$r->getBody();
    echo "  Status: {$r->getStatusCode()} (" . strlen($b) . " byte)" . PHP_EOL;
    // Cerca valori numerici nel HTML che sembrano kWh
    if (preg_match_all('/class="[^"]*(?:value|yield|pac|power)[^"]*"[^>]*>([^<]+)/i', $b, $vm)) {
        foreach ($vm[0] as $v) echo "  {$v}" . PHP_EOL;
    }
    // Cerca qualsiasi numero > 100 che potrebbe essere kWh
    if (preg_match_all('/>(\d{3,}(?:[\.,]\d+)?)\s*</', $b, $nm)) {
        echo "  Numeri grandi nel HTML:" . PHP_EOL;
        foreach (array_unique($nm[1]) as $n) echo "    {$n}" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  ERRORE" . PHP_EOL;
}
