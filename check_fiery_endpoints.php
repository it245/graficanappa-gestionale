<?php
/**
 * Esplora endpoint Fiery P400 / Canon V900 per trovare API funzionanti.
 * Uso: php check_fiery_endpoints.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$host = config('fiery.host');
$user = config('fiery.username');
$pass = config('fiery.password');
$key = config('fiery.api_key');
$base = "https://{$host}";

echo "=== Host: {$base} ===\n\n";

// Lista endpoint da provare (GET)
$getEndpoints = [
    '/',
    '/wt4/home/get_server_status',
    '/wt4/home/get_server_status?client_locale=it_IT',
    '/live/api/v5/serverStatus',
    '/live/api/v5/server/status',
    '/live/api/v4/serverStatus',
    '/api/v5/serverStatus',
    '/rest/v1/server/status',
    '/fiery/api/v5/serverStatus',
];

foreach ($getEndpoints as $ep) {
    try {
        $r = Http::withoutVerifying()->timeout(3)->get($base . $ep);
        printf("GET %-50s → %d (%d bytes)\n", $ep, $r->status(), strlen($r->body()));
        if ($r->status() === 200 && strlen($r->body()) < 300) {
            echo "     Body: " . trim(substr($r->body(), 0, 150)) . "\n";
        }
    } catch (\Exception $e) {
        printf("GET %-50s → ERR (%s)\n", $ep, substr($e->getMessage(), 0, 60));
    }
}

echo "\n=== POST login variants ===\n";

$loginEndpoints = [
    '/live/api/v5/login',
    '/live/api/v4/login',
    '/api/v5/login',
    '/login',
    '/fiery/api/login',
    '/rest/v1/login',
];

foreach ($loginEndpoints as $ep) {
    try {
        $r = Http::withoutVerifying()->timeout(3)->post($base . $ep, [
            'username' => $user,
            'password' => $pass,
            'accessrights' => $key,
        ]);
        printf("POST %-40s → %d\n", $ep, $r->status());
        if ($r->status() !== 403 && $r->status() !== 404 && strlen($r->body()) < 500) {
            echo "     Body: " . trim(substr($r->body(), 0, 200)) . "\n";
        }
    } catch (\Exception $e) {
        printf("POST %-40s → ERR (%s)\n", $ep, substr($e->getMessage(), 0, 60));
    }
}

echo "\n=== Pagine root (capire che server è) ===\n";
try {
    $r = Http::withoutVerifying()->timeout(3)->get($base . '/');
    echo "Status: {$r->status()}\n";
    $headers = $r->headers();
    echo "Headers chiave:\n";
    foreach (['Server', 'X-Powered-By', 'Content-Type', 'Location', 'Set-Cookie'] as $h) {
        if (isset($headers[$h])) {
            echo "  {$h}: " . (is_array($headers[$h]) ? implode(', ', $headers[$h]) : $headers[$h]) . "\n";
        }
    }
    // Cerca title HTML
    if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $r->body(), $m)) {
        echo "  <title>: " . trim($m[1]) . "\n";
    }
} catch (\Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
