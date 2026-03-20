<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$base = env('PRINECT_API_URL');
$user = env('PRINECT_API_USER');
$pass = env('PRINECT_API_PASS');

function api($url, $user, $pass, $params = []) {
    try {
        $r = Http::withBasicAuth($user, $pass)->timeout(15)->get($url, $params);
        return ['status' => $r->status(), 'data' => $r->json(), 'ok' => $r->successful()];
    } catch (\Exception $e) {
        return ['status' => 0, 'data' => null, 'ok' => false, 'error' => $e->getMessage()];
    }
}

function showKeys($data, $prefix = '  ') {
    if (!is_array($data)) return;
    foreach ($data as $k => $v) {
        if (is_array($v) && !isset($v[0])) {
            echo "{$prefix}{$k}: {object}" . PHP_EOL;
        } elseif (is_array($v)) {
            echo "{$prefix}{$k}: [array, " . count($v) . " items]" . PHP_EOL;
        } else {
            $val = $v === null ? 'NULL' : (is_bool($v) ? ($v ? 'true' : 'false') : substr((string)$v, 0, 80));
            echo "{$prefix}{$k}: {$val}" . PHP_EOL;
        }
    }
}

$jobId = '66779'; // commessa recente con attività
$deviceId = '4001';
$wsId = null;
$start = date('Y-m-d\TH:i:sP', strtotime('-1 day'));
$end = date('Y-m-d\TH:i:sP');

echo "============================================" . PHP_EOL;
echo "ESPLORAZIONE COMPLETA API PRINECT" . PHP_EOL;
echo "Job test: {$jobId} | Device: {$deviceId}" . PHP_EOL;
echo "============================================" . PHP_EOL . PHP_EOL;

// 1. /rest/version
echo "=== 1. /rest/version ===" . PHP_EOL;
$r = api("{$base}/rest/version", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
echo PHP_EOL;

// 2. /rest/device (lista dispositivi)
echo "=== 2. /rest/device ===" . PHP_EOL;
$r = api("{$base}/rest/device", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $devices = $r['data']['devices'] ?? [$r['data']];
    foreach ($devices as $d) {
        echo "  Device: " . ($d['id'] ?? '?') . " | " . ($d['name'] ?? '?') . " | status: " . ($d['status'] ?? '?') . PHP_EOL;
    }
    echo "  CHIAVI primo device: ";
    showKeys($devices[0] ?? []);
}
echo PHP_EOL;

// 3. /rest/device/{id}/activity (oggi)
echo "=== 3. /rest/device/{$deviceId}/activity (oggi) ===" . PHP_EOL;
$r = api("{$base}/rest/device/{$deviceId}/activity", $user, $pass, ['start' => $start, 'end' => $end]);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $acts = $r['data']['activities'] ?? [];
    echo "  Attività: " . count($acts) . PHP_EOL;
    if (!empty($acts[0])) {
        echo "  CHIAVI attività:" . PHP_EOL;
        showKeys($acts[0], '    ');
    }
}
echo PHP_EOL;

// 4. /rest/device/{id}/consumption (oggi)
echo "=== 4. /rest/device/{$deviceId}/consumption (oggi) ===" . PHP_EOL;
$r = api("{$base}/rest/device/{$deviceId}/consumption", $user, $pass, ['start' => $start, 'end' => $end]);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
echo PHP_EOL;

// 5. /rest/device/{id}/output (oggi) — NON TESTATO PRIMA
echo "=== 5. /rest/device/{$deviceId}/output (oggi) ===" . PHP_EOL;
$r = api("{$base}/rest/device/{$deviceId}/output", $user, $pass, ['start' => $start, 'end' => $end]);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
else echo "  NON DISPONIBILE" . PHP_EOL;
echo PHP_EOL;

// 6. /rest/devicegroup
echo "=== 6. /rest/devicegroup ===" . PHP_EOL;
$r = api("{$base}/rest/devicegroup", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
echo PHP_EOL;

// 7. /rest/job/{id} (dettaglio job)
echo "=== 7. /rest/job/{$jobId} ===" . PHP_EOL;
$r = api("{$base}/rest/job/{$jobId}", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $job = $r['data']['job'] ?? $r['data'];
    echo "  CHIAVI job:" . PHP_EOL;
    showKeys($job, '    ');
}
echo PHP_EOL;

// 8. /rest/job/{id}/workstep
echo "=== 8. /rest/job/{$jobId}/workstep ===" . PHP_EOL;
$r = api("{$base}/rest/job/{$jobId}/workstep", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $wss = $r['data']['worksteps'] ?? [];
    echo "  Workstep totali: " . count($wss) . PHP_EOL;
    foreach ($wss as $ws) {
        $types = implode(',', $ws['types'] ?? []);
        echo "    {$ws['name']} | status:{$ws['status']} | types:{$types} | buoni:" . ($ws['amountProduced'] ?? 0) . PHP_EOL;
        if (in_array('ConventionalPrinting', $ws['types'] ?? []) && !$wsId) {
            $wsId = $ws['id'];
        }
    }
    if (!empty($wss[0])) {
        echo "  CHIAVI workstep:" . PHP_EOL;
        showKeys($wss[0], '    ');
    }
}
echo PHP_EOL;

// 9. /rest/job/{id}/workstep/{wsId}/activity
if ($wsId) {
    echo "=== 9. /rest/job/{$jobId}/workstep/{$wsId}/activity ===" . PHP_EOL;
    $r = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/activity", $user, $pass);
    echo "  HTTP: {$r['status']}" . PHP_EOL;
    if ($r['ok']) {
        $acts = $r['data']['activities'] ?? [];
        echo "  Attività: " . count($acts) . PHP_EOL;
        if (!empty($acts[0])) {
            echo "  CHIAVI attività WS:" . PHP_EOL;
            showKeys($acts[0], '    ');
        }
    }
    echo PHP_EOL;

    // 10. /rest/job/{id}/workstep/{wsId}/inkConsumption
    echo "=== 10. /rest/job/{$jobId}/workstep/{$wsId}/inkConsumption ===" . PHP_EOL;
    $r = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/inkConsumption", $user, $pass);
    echo "  HTTP: {$r['status']}" . PHP_EOL;
    if ($r['ok']) showKeys($r['data']);
    echo PHP_EOL;

    // 11. /rest/job/{id}/workstep/{wsId}/quality
    echo "=== 11. /rest/job/{$jobId}/workstep/{$wsId}/quality ===" . PHP_EOL;
    $r = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/quality", $user, $pass);
    echo "  HTTP: {$r['status']}" . PHP_EOL;
    if ($r['ok']) showKeys($r['data']);
    else echo "  NON DISPONIBILE" . PHP_EOL;
    echo PHP_EOL;

    // 12. /rest/job/{id}/workstep/{wsId}/preview
    echo "=== 12. /rest/job/{$jobId}/workstep/{$wsId}/preview ===" . PHP_EOL;
    $r = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/preview", $user, $pass);
    echo "  HTTP: {$r['status']}" . PHP_EOL;
    if ($r['ok']) {
        $previews = $r['data']['previews'] ?? [];
        echo "  Preview disponibili: " . count($previews) . PHP_EOL;
    }
    echo PHP_EOL;
}

// 13. /rest/job/{id}/element
echo "=== 13. /rest/job/{$jobId}/element ===" . PHP_EOL;
$r = api("{$base}/rest/job/{$jobId}/element", $user, $pass, ['query' => 'ALL']);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $els = $r['data']['elements'] ?? [];
    echo "  Elementi: " . count($els) . PHP_EOL;
    if (!empty($els[0])) {
        echo "  CHIAVI elemento:" . PHP_EOL;
        showKeys($els[0], '    ');
    }
}
echo PHP_EOL;

// 14. /rest/job/{id}/milestone — NON TESTATO PRIMA
echo "=== 14. /rest/job/{$jobId}/milestone ===" . PHP_EOL;
$r = api("{$base}/rest/job/{$jobId}/milestone", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
else echo "  NON DISPONIBILE" . PHP_EOL;
echo PHP_EOL;

// 15. /rest/employee (lista operatori Prinect)
echo "=== 15. /rest/employee ===" . PHP_EOL;
$r = api("{$base}/rest/employee", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $emps = $r['data']['employees'] ?? [];
    echo "  Operatori: " . count($emps) . PHP_EOL;
    foreach ($emps as $e) {
        echo "    ID:{$e['id']} | {$e['name']}" . PHP_EOL;
    }
}
echo PHP_EOL;

// 16. /rest/masterdata/milestone
echo "=== 16. /rest/masterdata/milestone ===" . PHP_EOL;
$r = api("{$base}/rest/masterdata/milestone", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $ms = $r['data']['milestones'] ?? [];
    echo "  Milestone definite: " . count($ms) . PHP_EOL;
    foreach ($ms as $m) {
        echo "    {$m['id']} | {$m['name']}" . PHP_EOL;
    }
}
echo PHP_EOL;

// 17. /rest/ganging — NON TESTATO PRIMA
echo "=== 17. /rest/ganging ===" . PHP_EOL;
$r = api("{$base}/rest/ganging", $user, $pass);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) showKeys($r['data']);
else echo "  NON DISPONIBILE" . PHP_EOL;
echo PHP_EOL;

// 18. /rest/job?modifiedSince (ultimi 2gg)
echo "=== 18. /rest/job?modifiedSince (ultimi 2gg) ===" . PHP_EOL;
$since = date('Y-m-d\TH:i:sP', strtotime('-2 days'));
$r = api("{$base}/rest/job", $user, $pass, ['modifiedSince' => $since]);
echo "  HTTP: {$r['status']}" . PHP_EOL;
if ($r['ok']) {
    $jobs = $r['data']['jobs'] ?? [];
    echo "  Job modificati: " . count($jobs) . PHP_EOL;
    if (!empty($jobs[0])) {
        echo "  CHIAVI job lista:" . PHP_EOL;
        showKeys($jobs[0], '    ');
    }
}
echo PHP_EOL;

echo "=== FINE ESPLORAZIONE ===" . PHP_EOL;
