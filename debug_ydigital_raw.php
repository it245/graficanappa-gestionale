<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('YDIGITAL_API_KEY');

echo "=== Endpoint sensors/ (lista) ===\n";
$r = Http::withoutVerifying()->withHeaders(['X-API-Key' => $apiKey])->timeout(10)
    ->get('https://ysens.it/api/v1/sensors/');
echo "HTTP " . $r->status() . "\n";
echo $r->body() . "\n\n";

echo "=== Endpoint sensor latest ===\n";
$r2 = Http::withoutVerifying()->withHeaders(['X-API-Key' => $apiKey])->timeout(10)
    ->get('https://ysens.it/api/v1/sensors/207D2613CFD0/BreakBeam_1/latest/');
echo "HTTP " . $r2->status() . "\n";
echo $r2->body() . "\n\n";

echo "=== Endpoint readings recent ===\n";
$r3 = Http::withoutVerifying()->withHeaders(['X-API-Key' => $apiKey])->timeout(10)
    ->get('https://ysens.it/api/v1/sensors/207D2613CFD0/BreakBeam_1/readings/?limit=10');
echo "HTTP " . $r3->status() . "\n";
echo $r3->body() . "\n";
