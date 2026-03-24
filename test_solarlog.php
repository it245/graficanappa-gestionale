<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$portalUrl = 'https://solarlog-portal.it';
$user = 'vittorio81@katamail.com';
$pass = 'nappa2017';

echo "=== TEST SOLAR-LOG ===" . PHP_EOL;

// Usa Guzzle direttamente con cookie jar
$jar = new \GuzzleHttp\Cookie\CookieJar();
$client = new \GuzzleHttp\Client([
    'verify' => false,
    'cookies' => $jar,
    'timeout' => 15,
]);

// 1. Login
echo "1. Login..." . PHP_EOL;
$loginResp = $client->post("{$portalUrl}/974821.html", [
    'form_params' => [
        'username' => $user,
        'password' => $pass,
        'action' => 'login',
    ],
]);
echo "   Status: {$loginResp->getStatusCode()}" . PHP_EOL;
echo "   Cookies: " . count($jar->toArray()) . PHP_EOL;
foreach ($jar->toArray() as $c) {
    echo "   - {$c['Name']}={$c['Value']}" . PHP_EOL;
}

// 2. Pagina rendimenti
echo PHP_EOL . "2. Pagina rendimenti..." . PHP_EOL;
$rendResp = $client->get("{$portalUrl}/emulated_yieldov_3650.html");
$body = (string)$rendResp->getBody();
echo "   Status: {$rendResp->getStatusCode()}" . PHP_EOL;
echo "   Lunghezza: " . strlen($body) . " byte" . PHP_EOL;

// 3. Cerca dati kWh nel body
if (preg_match_all('/(\d+[\.,]?\d*)\s*(kWh|kW|MWh|Wh)/i', $body, $matches)) {
    echo PHP_EOL . "3. Dati energia trovati:" . PHP_EOL;
    foreach ($matches[0] as $m) {
        echo "   {$m}" . PHP_EOL;
    }
}

// Cerca anche numeri grandi che potrebbero essere rendimenti
if (preg_match_all('/yieldov[^"]*"[^"]*"|Pac|Pdc|Etoday|Etotal|power|yield/i', $body, $matches2)) {
    echo PHP_EOL . "4. Keyword energia trovate:" . PHP_EOL;
    foreach (array_unique($matches2[0]) as $m) {
        echo "   {$m}" . PHP_EOL;
    }
}

// Salva body per analisi
file_put_contents('solarlog_page.html', $body);
echo PHP_EOL . "Body salvato in solarlog_page.html (" . round(strlen($body)/1024) . " KB)" . PHP_EOL;

// 5. Prova pagine alternative
echo PHP_EOL . "5. Altre pagine..." . PHP_EOL;
$pagine = [
    '/974821.html',
    '/emulated_min_cur_3650.html',
    '/emulated_min_day_3650.html',
];
foreach ($pagine as $p) {
    try {
        $r = $client->get("{$portalUrl}{$p}");
        $b = (string)$r->getBody();
        echo "   {$p} → {$r->getStatusCode()} (" . round(strlen($b)/1024) . " KB)" . PHP_EOL;
        // Cerca kWh
        if (preg_match_all('/(\d+[\.,]?\d*)\s*(kWh|kW|MWh)/i', $b, $m3)) {
            foreach (array_slice($m3[0], 0, 3) as $v) echo "     → {$v}" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "   {$p} → ERRORE" . PHP_EOL;
    }
}
