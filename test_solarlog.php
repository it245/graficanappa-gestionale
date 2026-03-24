<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$portalUrl = 'https://solarlog-portal.it';
$user = 'vittorio81@katamail.com';
$pass = 'nappa2017';

echo "=== TEST SOLAR-LOG ===" . PHP_EOL;

// 1. Login
echo "1. Login..." . PHP_EOL;
$loginResp = Http::withoutVerifying()->asForm()->post("{$portalUrl}/974821.html", [
    'username' => $user,
    'password' => $pass,
    'action' => 'login',
]);

$cookies = $loginResp->cookies();
echo "   Status: {$loginResp->status()}" . PHP_EOL;
echo "   Cookies: " . count($cookies) . PHP_EOL;

// Salva i cookie per le richieste successive
$cookieJar = $loginResp->cookies();

// 2. Pagina rendimenti
echo "2. Pagina rendimenti..." . PHP_EOL;
$rendResp = Http::withoutVerifying()->withCookies($cookieJar->toArray(), 'solarlog-portal.it')
    ->get("{$portalUrl}/emulated_yieldov_3650.html");

echo "   Status: {$rendResp->status()}" . PHP_EOL;
echo "   Lunghezza: " . strlen($rendResp->body()) . " byte" . PHP_EOL;

// 3. Cerca dati nel body
$body = $rendResp->body();

// Cerca pattern per kWh, potenza, rendimento
if (preg_match_all('/(\d+[\.,]\d+)\s*(kWh|kW|MWh)/i', $body, $matches)) {
    echo "3. Dati trovati:" . PHP_EOL;
    foreach ($matches[0] as $i => $m) {
        echo "   {$m}" . PHP_EOL;
    }
}

// Salva il body per analisi
$htmlFile = 'solarlog_page.html';
file_put_contents($htmlFile, $body);
echo PHP_EOL . "Body salvato in {$htmlFile} (" . round(strlen($body)/1024) . " KB)" . PHP_EOL;

// 4. Prova anche l'API diretta (se disponibile)
echo PHP_EOL . "4. Prova API..." . PHP_EOL;
$apiUrls = [
    "{$portalUrl}/974821/api/v1/current",
    "{$portalUrl}/974821/api/current",
    "{$portalUrl}/getjp",
];

foreach ($apiUrls as $url) {
    try {
        $r = Http::withoutVerifying()->withCookies($cookieJar->toArray(), 'solarlog-portal.it')
            ->timeout(5)->get($url);
        echo "   {$url} → {$r->status()} (" . strlen($r->body()) . " byte)" . PHP_EOL;
        if ($r->status() === 200 && strlen($r->body()) < 5000) {
            echo "   Body: " . substr($r->body(), 0, 200) . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "   {$url} → ERRORE: " . substr($e->getMessage(), 0, 60) . PHP_EOL;
    }
}
