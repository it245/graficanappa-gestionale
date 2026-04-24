<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== nettime_anagrafica con GARGIULO ===\n";
$nt = DB::table('nettime_anagrafica')->where('cognome', 'like', '%GARGIULO%')->get();
foreach ($nt as $r) echo "  matricola={$r->matricola} cognome={$r->cognome} nome={$r->nome}\n";

echo "\n=== operatori con Gargiulo ===\n";
$op = DB::table('operatori')->where('nome', 'like', '%Gargiulo%')->orWhere('nome','like','%GARGIULO%')->get();
foreach ($op as $r) {
    echo "  id={$r->id} nome={$r->nome} matricola=" . ($r->matricola ?? 'NULL') . " reparto=" . ($r->reparto ?? '-') . "\n";
}
