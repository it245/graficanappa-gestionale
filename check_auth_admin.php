<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tabelle con admin/owner/user nel nome ===\n";
foreach (DB::select('SHOW TABLES') as $t) {
    $v = array_values((array)$t)[0];
    if (preg_match('/admin|owner|user/i', $v)) echo "  $v\n";
}

echo "\n=== Auth config ===\n";
$guards = config('auth.guards');
foreach ($guards as $name => $cfg) {
    echo "  guard '$name' driver={$cfg['driver']} provider=" . ($cfg['provider'] ?? '?') . "\n";
}

echo "\n=== Providers ===\n";
foreach (config('auth.providers') as $name => $cfg) {
    echo "  '$name' driver={$cfg['driver']} model=" . ($cfg['model'] ?? '?') . " table=" . ($cfg['table'] ?? '?') . "\n";
}

echo "\n=== Default guard ===\n";
echo "  " . config('auth.defaults.guard') . "\n";
