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

// Dashboard page — analizza tutti i JS e link AJAX
$resp = $client->get("{$portalUrl}/974821.html");
$body = (string)$resp->getBody();

echo "=== ANALISI JAVASCRIPT DASHBOARD ===" . PHP_EOL;

// Trova URL AJAX nei JS
if (preg_match_all('/(?:url|href|src|ajax|fetch|load)\s*[\(:"\']\s*["\']?([^"\'>\s\)]+)/i', $body, $m)) {
    $urls = array_unique($m[1]);
    echo "URL trovati: " . count($urls) . PHP_EOL;
    foreach ($urls as $u) {
        if (strlen($u) > 5 && !str_contains($u, '.css') && !str_contains($u, '.png') && !str_contains($u, '.ico')) {
            echo "  {$u}" . PHP_EOL;
        }
    }
}

// Cerca funzioni JS che caricano dati
echo PHP_EOL . "=== FUNZIONI JS DATI ===" . PHP_EOL;
if (preg_match_all('/function\s+(\w*(?:load|get|fetch|update|refresh|data|yield|power|pac)\w*)\s*\(/i', $body, $m2)) {
    foreach (array_unique($m2[0]) as $f) echo "  {$f}" . PHP_EOL;
}

// Cerca $.ajax, $.get, $.post, fetch
if (preg_match_all('/\$\.(?:ajax|get|post)\s*\(\s*["\']([^"\']+)/i', $body, $m3)) {
    echo PHP_EOL . "=== AJAX CALLS ===" . PHP_EOL;
    foreach (array_unique($m3[1]) as $u) echo "  {$u}" . PHP_EOL;
}

// Cerca "pac", "etoday", "etotal" nel JS
echo PHP_EOL . "=== KEYWORD ENERGIA NEL JS ===" . PHP_EOL;
if (preg_match_all('/\b(pac|etoday|etotal|power_now|yield_today|yield_total|currentPower|dailyYield)\b/i', $body, $m4)) {
    foreach (array_unique($m4[0]) as $k) echo "  {$k}" . PHP_EOL;
}

// Cerca tutti i .js esterni caricati
echo PHP_EOL . "=== JS ESTERNI ===" . PHP_EOL;
if (preg_match_all('/src="([^"]*\.js[^"]*)"/i', $body, $m5)) {
    foreach ($m5[1] as $js) echo "  {$js}" . PHP_EOL;
}

// Prova a scaricare il primo JS esterno e cercare endpoint
if (!empty($m5[1])) {
    foreach (array_slice($m5[1], 0, 3) as $jsUrl) {
        $fullUrl = str_starts_with($jsUrl, 'http') ? $jsUrl : "{$portalUrl}/{$jsUrl}";
        try {
            $jsResp = $client->get($fullUrl);
            $jsBody = (string)$jsResp->getBody();
            echo PHP_EOL . "=== JS: {$jsUrl} (" . round(strlen($jsBody)/1024) . " KB) ===" . PHP_EOL;
            // Cerca AJAX URL nel JS
            if (preg_match_all('/\$\.(?:ajax|get|post)\s*\(\s*["\']([^"\']+)/i', $jsBody, $mjs)) {
                foreach (array_unique($mjs[1]) as $u) echo "  AJAX: {$u}" . PHP_EOL;
            }
            if (preg_match_all('/url\s*:\s*["\']([^"\']+)/i', $jsBody, $mjs2)) {
                foreach (array_unique($mjs2[1]) as $u) {
                    if (strlen($u) > 5 && !str_contains($u, '.css')) echo "  URL: {$u}" . PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            echo PHP_EOL . "=== JS: {$jsUrl} → ERRORE ===" . PHP_EOL;
        }
    }
}
