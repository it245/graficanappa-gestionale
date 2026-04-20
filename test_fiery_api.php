<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$host = config('fiery.host');
$user = config('fiery.username');
$baseUrl = 'https://' . $host;

echo "Host: {$host}\n";
echo "User: {$user}\n\n";

// isOnline check
$fiery = app(\App\Http\Services\FieryService::class);
echo "isOnline: " . ($fiery->isOnline() ? 'YES' : 'NO') . "\n\n";

// Login
$r = Http::withoutVerifying()->timeout(10)
    ->post($baseUrl . '/live/api/v5/login', [
        'username' => $user,
        'password' => config('fiery.password'),
        'accessrights' => config('fiery.api_key'),
    ]);
echo "Login status: " . $r->status() . "\n";
if (!$r->successful()) {
    echo "Body: " . substr($r->body(), 0, 500) . "\n";
    exit(1);
}

$cookies = [];
foreach ($r->cookies() as $c) $cookies[$c->getName()] = $c->getValue();

// Accounting
$r = Http::withoutVerifying()->timeout(120)
    ->withCookies($cookies, $host)
    ->get($baseUrl . '/live/api/v5/accounting');

echo "Accounting status: " . $r->status() . "\n";
echo "Size: " . strlen($r->body()) . " byte\n";

$json = $r->json();
echo "Keys: " . implode(', ', array_keys($json ?? [])) . "\n";

$items = $json['data']['items'] ?? $json ?? [];
echo "Items: " . (is_array($items) ? count($items) : 'NOT ARRAY') . "\n";

if (is_array($items) && count($items) > 0) {
    echo "\nPrimo item:\n";
    print_r($items[0]);
}
