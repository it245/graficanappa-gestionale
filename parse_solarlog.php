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

echo "=== SOLAR-LOG DATI ===" . PHP_EOL;

// Prova la pagina del singolo impianto (6808)
$pagine = [
    'Dashboard' => "/974821.html",
    'YieldOverview' => "/emulated_yieldov_3650.html",
    'PlantDetail' => "/974821/6808.html",
    'PlantJSON' => "/974821/plant/6808/current",
    'PlantAPI' => "/api/v1/plants/6808",
    'AJAX YieldPlant' => "/974821/ajax/yieldOverviewPlant/6808",
    'AJAX Dashboard' => "/974821/ajax/dashboard",
    'AJAX Current' => "/974821/ajax/current",
    'AJAX MinCur' => "/974821/ajax/min_cur",
    'AJAX DayYield' => "/974821/ajax/day_yield",
];

foreach ($pagine as $nome => $path) {
    try {
        $r = $client->get("{$portalUrl}{$path}");
        $b = (string)$r->getBody();
        $len = strlen($b);
        echo PHP_EOL . "=== {$nome} ({$path}) → {$r->getStatusCode()} ({$len} byte) ===" . PHP_EOL;
        if ($len < 3000) {
            echo $b . PHP_EOL;
        } else {
            // Cerca numeri con kWh/kW
            if (preg_match_all('/(\d+[\.,]?\d*)\s*(kWh|kW|MWh|Wh)/i', $b, $m)) {
                foreach (array_slice($m[0], 0, 10) as $v) echo "  {$v}" . PHP_EOL;
            }
            // Cerca JSON inline
            if (preg_match_all('/"pac"\s*:\s*(\d+)|"etoday"\s*:\s*([\d\.]+)|"etotal"\s*:\s*([\d\.]+)/i', $b, $m2)) {
                echo "  pac/etoday/etotal: " . implode(', ', array_filter($m2[0])) . PHP_EOL;
            }
            // Cerca class=value o data-value
            if (preg_match_all('/data-value="([^"]+)"|class="value[^"]*"[^>]*>([^<]+)/i', $b, $m3)) {
                foreach (array_slice($m3[0], 0, 10) as $v) echo "  {$v}" . PHP_EOL;
            }
        }
    } catch (\Exception $e) {
        echo PHP_EOL . "=== {$nome} ({$path}) → ERRORE ===" . PHP_EOL;
    }
}
