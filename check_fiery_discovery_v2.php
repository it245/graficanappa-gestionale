<?php
/**
 * Discovery avanzato Fiery Canon V900: path base alternativi + dettaglio job
 * Uso: php check_fiery_discovery_v2.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$host = config('fiery.host');
$user = config('fiery.username');
$pass = config('fiery.password');
$key = config('fiery.api_key');
$baseUrl = "https://{$host}";

// Login
echo "=== Login ===\n";
$login = Http::withoutVerifying()->timeout(5)->post("{$baseUrl}/live/api/v5/login", [
    'username' => $user, 'password' => $pass, 'accessrights' => $key,
]);
if (!$login->successful()) { echo "fail\n"; exit(1); }
$cookieHeader = '';
foreach ((array) ($login->headers()['Set-Cookie'] ?? []) as $sc) {
    if (preg_match('/^([^=]+)=([^;]+)/', $sc, $m)) $cookieHeader .= $m[1] . '=' . $m[2] . '; ';
}
$http = Http::withoutVerifying()->timeout(5)->withHeaders(['Cookie' => trim($cookieHeader, '; ')]);
echo "OK\n\n";

// Prende un job_id dal /jobs/printing per testare dettaglio
echo "=== Prendi ID job stampa ===\n";
$resp = $http->get("{$baseUrl}/live/api/v5/jobs/printing");
$jobs = $resp->json('data.items', []);
$firstJobId = null;
if (!empty($jobs)) {
    $firstJobId = $jobs[0]['id'] ?? null;
    echo "Job id: {$firstJobId}\n";
}

// Path base alternativi
$basePaths = [
    '/live/api/v5',
    '/live/api/v4',
    '/live/api/v3',
    '/live/api/v2',
    '/live/api/v1',
    '/wt4/admin',
    '/wt4/common',
    '/wt4/jobs',
    '/wt4/accounting',
    '/api',
    '/rest',
    '/ws',
];

$subPaths = [
    '/accounting',
    '/accounting/jobs',
    '/counters',
    '/consumables',
    '/toners',
    '/paper',
    '/trays',
    '/info',
    '/version',
    '/log',
    '/reports',
    '/status',
    '/alerts',
    '/warnings',
    '/settings',
];

echo "\n=== Combinazioni base + sub ===\n";
$found = [];
foreach ($basePaths as $bp) {
    foreach ($subPaths as $sp) {
        $url = $baseUrl . $bp . $sp;
        try {
            $r = $http->get($url);
            if ($r->status() === 200 && strlen($r->body()) > 50) {
                printf("  ✓ %-50s (%d bytes)\n", $bp . $sp, strlen($r->body()));
                $found[] = $bp . $sp;
            }
        } catch (\Exception $e) {}
    }
}

echo "\n=== Endpoint job dettaglio ===\n";
if ($firstJobId) {
    $endpoints = [
        "/live/api/v5/jobs/{$firstJobId}",
        "/live/api/v5/jobs/{$firstJobId}/properties",
        "/live/api/v5/jobs/{$firstJobId}/preview",
        "/live/api/v5/jobs/{$firstJobId}/status",
        "/live/api/v5/jobs/{$firstJobId}/accounting",
        "/live/api/v5/jobs/{$firstJobId}/log",
    ];
    foreach ($endpoints as $ep) {
        try {
            $r = $http->get($baseUrl . $ep);
            if ($r->successful()) {
                printf("  ✓ %-50s → %d (%d bytes)\n", basename($ep), $r->status(), strlen($r->body()));
            }
        } catch (\Exception $e) {}
    }
}

// Esplora WebTools legacy
echo "\n=== WebTools legacy discovery ===\n";
$wt = [
    '/wt4/home/get_device_status',
    '/wt4/home/get_server_config',
    '/wt4/home/get_accounting',
    '/wt4/home/get_counters',
    '/wt4/home/get_consumables',
    '/wt4/home/get_paper_trays',
    '/wt4/home/get_toner_levels',
    '/wt4/home/get_printer_info',
    '/wt4/home/get_warnings',
    '/wt4/home/get_jobs',
    '/wt4/home/get_queue',
];
foreach ($wt as $ep) {
    try {
        $r = $http->get($baseUrl . $ep);
        if ($r->status() === 200 && strlen($r->body()) > 30) {
            printf("  ✓ %-45s (%d bytes)\n", $ep, strlen($r->body()));
        }
    } catch (\Exception $e) {}
}

// Esempio contenuto primo job printed
echo "\n=== Struttura 1° job printed ===\n";
$resp = $http->get("{$baseUrl}/live/api/v5/jobs/printed");
$items = $resp->json('data.items', []);
if (!empty($items)) {
    $j = $items[0];
    echo "Chiavi top: " . implode(', ', array_keys($j)) . "\n";
    echo "JSON prima 800 char:\n" . substr(json_encode($j, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), 0, 800) . "\n";
}
