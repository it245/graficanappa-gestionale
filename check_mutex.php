<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('cache')
    ->where('key', 'LIKE', '%schedule%')
    ->orWhere('key', 'LIKE', '%mutex%')
    ->orWhere('key', 'LIKE', '%framework%')
    ->orWhere('key', 'LIKE', '%overlap%')
    ->get();

echo "Trovate " . count($rows) . " righe cache con pattern schedule/mutex/framework/overlap:\n\n";
foreach ($rows as $r) {
    $exp = $r->expiration ?? 0;
    $when = $exp > 0 ? date('Y-m-d H:i:s', $exp) : '?';
    echo "  key={$r->key} | exp=$when\n";
}

// Vedi tutte le chiavi cache
echo "\n\nTutte le chiavi cache (primi 100):\n";
$all = DB::table('cache')->select('key', 'expiration')->limit(100)->get();
foreach ($all as $r) {
    $exp = $r->expiration ?? 0;
    $when = $exp > 0 ? date('Y-m-d H:i:s', $exp) : '?';
    echo "  $when  {$r->key}\n";
}

echo "\nTotale righe cache: " . DB::table('cache')->count() . "\n";
