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

$rpcUrl = "{$portalUrl}/sds/rpc.php?sessid={$sessid}";
$sOptions = base64_encode(serialize([
    'showPlantGroups' => false,
    'allowExport' => false,
    'showSearch' => false,
    'showStatus' => false,
    'yieldMode' => 'absolute',
    'showDevices' => ['6808'],
    'viewMode' => 'frontend',
    'default_sorting' => null,
    'showReferenceWeather' => false,
    'showReferencePlants' => false,
]));

echo "=== PROVA RPC sdsYieldOverview ===" . PHP_EOL . PHP_EOL;

$calls = [
    ['module' => 'sdsYieldOverview', 'func' => 'openPlant', 'yieldOverviewPlant' => 6808, 'sOptions' => $sOptions],
    ['module' => 'sdsYieldOverview', 'func' => 'openGroup', 'yieldOverviewGroup' => -1, 'sOptions' => $sOptions],
    ['module' => 'sdsYieldOverview', 'func' => 'getErrorTooltip', 'plantId' => 6808],
    ['module' => 'sdsPlantMonitoring', 'func' => 'getPlantData', 'plantId' => 6808],
    ['module' => 'sdsPlantMonitoring', 'func' => 'getCurrentData', 'plantId' => 6808],
    ['module' => 'sdsPlantMonitoring', 'func' => 'getDashboardData', 'plantId' => 6808],
];

foreach ($calls as $call) {
    try {
        $r = $client->post($rpcUrl, [
            'form_params' => $call,
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ]);
        $b = (string)$r->getBody();
        $label = "{$call['module']}/{$call['func']}";
        echo "=== {$label} → {$r->getStatusCode()} (" . strlen($b) . " byte) ===" . PHP_EOL;
        if (strlen($b) > 52 && strlen($b) < 5000) {
            echo $b . PHP_EOL;
        } elseif (strlen($b) >= 5000) {
            // Cerca kWh e numeri
            echo "(troppo grande, cerco dati)" . PHP_EOL;
            if (preg_match_all('/(\d+[\.,]?\d*)\s*(kWh|kW|MWh)/i', $b, $m)) {
                foreach (array_slice($m[0], 0, 10) as $v) echo "  {$v}" . PHP_EOL;
            }
            if (preg_match_all('/>(\d{2,}(?:[\.,]\d+)?)\s*</', $b, $nm)) {
                $nums = array_unique($nm[1]);
                echo "  Numeri: " . implode(', ', array_slice($nums, 0, 10)) . PHP_EOL;
            }
            // Salva
            $fname = str_replace(['/', ' '], '_', $label) . '.html';
            file_put_contents($fname, $b);
            echo "  Salvato in {$fname}" . PHP_EOL;
        } else {
            echo "(52 byte = errore)" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "=== {$call['module']}/{$call['func']} → ERRORE ===" . PHP_EOL;
    }
    echo PHP_EOL;
}
