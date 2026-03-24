<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$portalUrl = 'https://solarlog-portal.it';
$user = 'vittorio81@katamail.com';
$pass = 'nappa2017';

$jar = new \GuzzleHttp\Cookie\CookieJar();
$client = new \GuzzleHttp\Client(['verify' => false, 'cookies' => $jar, 'timeout' => 15]);

$client->post("{$portalUrl}/974821.html", [
    'form_params' => ['username' => $user, 'password' => $pass, 'action' => 'login'],
]);

$sessid = '';
foreach ($jar->toArray() as $c) {
    if ($c['Name'] === 'SDS-Portal-V2') $sessid = $c['Value'];
}

// Scarica JS e cerca tutti i jQuery.ajax con data:
$jsResp = $client->get("{$portalUrl}/sds/template/default/javascript/compressed.js?v=3.5.15-24");
$js = (string)$jsResp->getBody();

echo "=== TUTTE LE CHIAMATE AJAX NEL JS ===" . PHP_EOL;

// Cerca pattern: jQuery.ajax({ ... data: { ... module: ... action: ...
if (preg_match_all('/jQuery\.ajax\(\{[^}]{0,500}data\s*:\s*\{([^}]{10,300})\}/s', $js, $m)) {
    echo "Chiamate AJAX trovate: " . count($m[1]) . PHP_EOL . PHP_EOL;
    foreach (array_slice($m[1], 0, 20) as $i => $data) {
        $data = trim(preg_replace('/\s+/', ' ', $data));
        echo "  #{$i}: {$data}" . PHP_EOL;
    }
}

// Cerca specificamente "module" nel JS
echo PHP_EOL . "=== MODULI RPC ===" . PHP_EOL;
if (preg_match_all('/module\s*:\s*["\']([^"\']+)/i', $js, $m2)) {
    foreach (array_unique($m2[1]) as $mod) echo "  module: {$mod}" . PHP_EOL;
}

// Cerca "YieldOverview" nelle vicinanze di ajax
echo PHP_EOL . "=== YIELD AJAX CONTEXT ===" . PHP_EOL;
$pos = 0;
while (($pos = stripos($js, 'YieldOverview', $pos)) !== false) {
    $context = substr($js, max(0, $pos - 200), 500);
    // Cerca ajax/data/module/action nelle vicinanze
    if (preg_match('/(?:ajax|data|module|action|rpc)/i', $context)) {
        $clean = preg_replace('/\s+/', ' ', $context);
        echo "  ..." . substr($clean, 0, 200) . "..." . PHP_EOL . PHP_EOL;
    }
    $pos += 13;
}

// Prova RPC con module
echo PHP_EOL . "=== PROVA RPC CON MODULE ===" . PHP_EOL;
$rpcUrl = "{$portalUrl}/sds/rpc.php?sessid={$sessid}";

$calls = [
    ['module' => 'YieldOverview', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'YieldOverviewPlant', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'Dashboard', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'Plant', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'PlantOverview', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'YieldDay', 'action' => 'load', 'PlantId' => 6808],
    ['module' => 'MinValues', 'action' => 'load', 'PlantId' => 6808],
];

foreach ($calls as $call) {
    try {
        $r = $client->post($rpcUrl, [
            'form_params' => $call,
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ]);
        $b = (string)$r->getBody();
        $label = $call['module'] . '/' . $call['action'];
        echo "  {$label} → {$r->getStatusCode()} (" . strlen($b) . " byte)" . PHP_EOL;
        if (strlen($b) < 3000 && strlen($b) > 52) {
            echo "    → " . substr($b, 0, 300) . PHP_EOL;
        }
    } catch (\Exception $e) {}
}
