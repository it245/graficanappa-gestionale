<?php
/**
 * Diagnosi connessione Fiery P400 (Canon imagePRESS V900)
 * Uso: php check_fiery_connessione.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$host = config('fiery.host');
$user = config('fiery.username');
$pass = config('fiery.password');
$key = config('fiery.api_key');

echo "=== Config Fiery ===\n";
echo "Host: {$host}\n";
echo "User: {$user}\n";
echo "API Key: " . ($key ? substr($key, 0, 8) . '...' : 'VUOTA ❌') . "\n\n";

echo "=== Test 1: WebTools legacy (/wt4/home) ===\n";
try {
    $r = Http::timeout(5)->get("http://{$host}/wt4/home/get_server_status", ['client_locale' => 'it_IT']);
    echo "Status HTTP: {$r->status()}\n";
    echo "Body (primi 200 char): " . substr($r->body(), 0, 200) . "\n\n";
} catch (\Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n\n";
}

echo "=== Test 2: API v5 login ===\n";
try {
    $r = Http::timeout(8)->post("http://{$host}/live/api/v5/login", [
        'username' => $user,
        'password' => $pass,
        'accessrights' => $key,
    ]);
    echo "Status HTTP: {$r->status()}\n";
    $body = $r->body();
    echo "Body: " . substr($body, 0, 400) . "\n\n";
} catch (\Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n\n";
}

echo "=== Test 3: Ping host ===\n";
$output = [];
$ret = 0;
exec("ping -n 2 {$host}", $output, $ret);
echo implode("\n", $output) . "\n";
echo "Exit code: {$ret}\n\n";

echo "=== Suggerimenti ===\n";
echo "- Se ping OK ma HTTP fallisce: Fiery acceso, WebServer disabilitato o porta diversa\n";
echo "- Se API v5 torna 401: credenziali/api_key errate o scadute\n";
echo "- Se API v5 torna 200: il problema è WebTools, aggiorna codice per usare v5\n";
echo "- Se ping fallisce: Fiery spento/IP cambiato/rete diversa\n";
