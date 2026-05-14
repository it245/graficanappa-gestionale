<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$oggi = date('Y-m-d');

$tot = DB::table('nettime_timbrature')->whereDate('data_ora', $oggi)->count();
echo "Totale timbrature oggi ($oggi): $tot\n";

$entrate = DB::table('nettime_timbrature')
    ->whereDate('data_ora', $oggi)
    ->where('verso', 'E')
    ->count();
echo "Entrate (verso=E): $entrate\n";

$ultime = DB::table('nettime_timbrature')->orderBy('data_ora', 'desc')->limit(5)->get();
echo "\nUltime 5 timbrature DB:\n";
foreach ($ultime as $t) {
    echo "  {$t->data_ora} | matr {$t->matricola} | verso {$t->verso}\n";
}
