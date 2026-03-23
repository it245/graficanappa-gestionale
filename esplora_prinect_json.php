<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$base = env('PRINECT_API_URL');
$user = env('PRINECT_API_USER');
$pass = env('PRINECT_API_PASS');
$deviceId = '4001';
$jobId = $argv[1] ?? '66779';
$start = date('Y-m-d\TH:i:sP', strtotime('-1 day'));
$end = date('Y-m-d\TH:i:sP');

function api($url, $user, $pass, $params = []) {
    try {
        $r = Http::withBasicAuth($user, $pass)->timeout(15)->get($url, $params);
        return $r->successful() ? $r->json() : ['_error' => $r->status()];
    } catch (\Exception $e) {
        return ['_error' => $e->getMessage()];
    }
}

$results = [];

$results['version'] = api("{$base}/rest/version", $user, $pass);
$results['device'] = api("{$base}/rest/device", $user, $pass);
$results['device_activity_today'] = api("{$base}/rest/device/{$deviceId}/activity", $user, $pass, ['start' => $start, 'end' => $end]);
$results['device_consumption'] = api("{$base}/rest/device/{$deviceId}/consumption", $user, $pass, ['start' => $start, 'end' => $end]);
$results['device_output'] = api("{$base}/rest/device/{$deviceId}/output", $user, $pass, ['start' => $start, 'end' => $end]);
$results['devicegroup'] = api("{$base}/rest/devicegroup", $user, $pass);
$results['job'] = api("{$base}/rest/job/{$jobId}", $user, $pass);
$results['job_worksteps'] = api("{$base}/rest/job/{$jobId}/workstep", $user, $pass);
$results['job_elements'] = api("{$base}/rest/job/{$jobId}/element", $user, $pass, ['query' => 'ALL']);
$results['employees'] = api("{$base}/rest/employee", $user, $pass);
$results['jobs_modified_2d'] = api("{$base}/rest/job", $user, $pass, ['modifiedSince' => date('Y-m-d\TH:i:sP', strtotime('-2 days'))]);

// Workstep activities e ink per il primo ConventionalPrinting
$wss = $results['job_worksteps']['worksteps'] ?? [];
foreach ($wss as $ws) {
    if (in_array('ConventionalPrinting', $ws['types'] ?? [])) {
        $wsId = $ws['id'];
        $results['ws_activity_' . $ws['name']] = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/activity", $user, $pass);
        $results['ws_ink_' . $ws['name']] = api("{$base}/rest/job/{$jobId}/workstep/{$wsId}/inkConsumption", $user, $pass);
        break;
    }
}

$outFile = 'public/prinect_api_dump.json';
file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Salvato in {$outFile} (" . round(filesize($outFile) / 1024) . " KB)" . PHP_EOL;
echo "Visualizza: http://192.168.1.60/prinect_api_dump.json" . PHP_EOL;
echo "Oppure: http://192.168.1.60/prinect-explorer" . PHP_EOL;
