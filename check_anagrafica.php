<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Sample nettime_anagrafica:\n";
$a = DB::table('nettime_anagrafica')->limit(5)->get();
foreach ($a as $r) print_r((array)$r);

echo "\nSample nettime_timbrature oggi:\n";
$t = DB::table('nettime_timbrature')->whereDate('data_ora', today())->limit(5)->get();
foreach ($t as $r) print_r((array)$r);

echo "\nMatricole presenti oggi vs anagrafica match:\n";
$mat = DB::table('nettime_timbrature')->whereDate('data_ora', today())
    ->where('verso','E')->distinct()->pluck('matricola');
echo "Matricole timbrature: " . implode(',', $mat->take(5)->toArray()) . "\n";
foreach ($mat->take(5) as $m) {
    $found = DB::table('nettime_anagrafica')->where('matricola', $m)->first();
    echo "  $m -> " . ($found ? "{$found->cognome} {$found->nome}" : 'NOT FOUND') . "\n";
}
