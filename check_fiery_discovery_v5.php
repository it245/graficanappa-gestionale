<?php
/**
 * Discovery completo API v5 Fiery Canon V900 per def2.0
 * Uso: php check_fiery_discovery_v5.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$host = config('fiery.host');
$user = config('fiery.username');
$pass = config('fiery.password');
$key = config('fiery.api_key');
$base = "https://{$host}/live/api/v5";

$cookie = null;

// Login per ottenere cookie sessione
echo "=== Login API v5 ===\n";
$login = Http::withoutVerifying()->timeout(5)->post("{$base}/login", [
    'username' => $user,
    'password' => $pass,
    'accessrights' => $key,
]);
if ($login->successful()) {
    $cookie = $login->cookies();
    echo "✓ Login OK\n\n";
} else {
    echo "✗ Login fallito ({$login->status()})\n\n";
    exit(1);
}

$http = Http::withoutVerifying()->timeout(5)->withCookies($cookie->toArray(), $host);

// Endpoint GET da esplorare — ampio set API v5 tipica Fiery
$endpoints = [
    // Server
    '/server/status',
    '/server/info',
    '/server/config',
    '/server/alerts',
    '/server/calibration',
    // Device (printer)
    '/device',
    '/device/status',
    '/device/info',
    '/device/consumables',
    '/device/counters',
    '/device/trays',
    '/device/finishers',
    '/device/alerts',
    '/device/capabilities',
    // Jobs
    '/jobs',
    '/jobs/printed',
    '/jobs/held',
    '/jobs/printing',
    '/jobs/queued',
    '/jobs/archived',
    '/jobs/error',
    // Queues
    '/queues',
    '/queues/print',
    '/queues/hold',
    '/queues/direct',
    // Accounting
    '/accounting',
    '/accounting/jobs',
    '/accounting/totals',
    '/accounting/summary',
    // Users
    '/users',
    '/users/me',
    '/groups',
    // Media/presets
    '/media',
    '/presets',
    '/workflows',
    // Notifiche/log
    '/notifications',
    '/log',
    '/events',
    // Varie
    '/license',
    '/version',
    '/models',
];

echo "=== Discovery endpoint GET ===\n";
$found = [];
foreach ($endpoints as $ep) {
    try {
        $r = $http->get($base . $ep);
        $status = $r->status();
        $bytes = strlen($r->body());

        if ($status === 200) {
            printf("  ✓ %-30s → %d (%d bytes)\n", $ep, $status, $bytes);
            $json = $r->json();
            // Mostra prime chiavi per capire struttura
            if (is_array($json)) {
                $keys = array_keys($json);
                $kind = $json['data']['kind'] ?? ($json['kind'] ?? '-');
                printf("      kind=%s keys=%s\n", $kind, implode(',', array_slice($keys, 0, 5)));
                $found[] = ['ep' => $ep, 'status' => $status, 'bytes' => $bytes, 'kind' => $kind];
            }
        } elseif ($status === 404) {
            // silente
        } elseif ($status === 401 || $status === 403) {
            printf("  ⚠ %-30s → %d (auth richiesta)\n", $ep, $status);
        } else {
            printf("  ? %-30s → %d\n", $ep, $status);
        }
    } catch (\Exception $e) {
        // skip
    }
}

echo "\n=== Endpoint funzionanti (200): " . count($found) . " ===\n";
foreach ($found as $f) {
    printf("  %s %s\n", str_pad($f['ep'], 30), $f['kind']);
}

// Dump dettaglio primi 3 endpoint principali
$prioritari = ['/device/consumables', '/device/counters', '/device/trays'];
echo "\n=== Dettaglio endpoint prioritari ===\n";
foreach ($prioritari as $ep) {
    echo "\n--- {$ep} ---\n";
    try {
        $r = $http->get($base . $ep);
        if ($r->successful()) {
            echo substr($r->body(), 0, 1500) . "\n";
        } else {
            echo "(status {$r->status()})\n";
        }
    } catch (\Exception $e) {
        echo "ERR\n";
    }
}
