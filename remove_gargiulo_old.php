<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$n = DB::table('nettime_anagrafica')->where('matricola', '000653')->delete();
echo "Rimossi {$n} record con matricola 000653 (Gargiulo Vincenzo duplicato)\n";

echo "\nResiduo GARGIULO:\n";
foreach (DB::table('nettime_anagrafica')->where('cognome','like','%GARGIULO%')->get() as $r) {
    echo "  matricola={$r->matricola} cognome={$r->cognome} nome={$r->nome}\n";
}
