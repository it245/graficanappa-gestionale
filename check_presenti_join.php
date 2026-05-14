<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$oggi = date('Y-m-d');

// 1. Anagrafica esistente
$anag = DB::table('nettime_anagrafica')->limit(3)->get();
echo "Anagrafica sample:\n";
foreach ($anag as $a) {
    echo "  matr={$a->matricola} ({$a->cognome} {$a->nome})\n";
}

// 2. JOIN entrate oggi
$rows = DB::table('nettime_timbrature as t')
    ->join('nettime_anagrafica as na', 'na.matricola', '=', 't.matricola')
    ->whereDate('t.data_ora', $oggi)
    ->where('t.verso', 'E')
    ->select('t.matricola', 'na.cognome', 'na.nome', 't.data_ora')
    ->limit(5)
    ->get();
echo "\nJOIN entrate oggi:\n";
foreach ($rows as $r) {
    echo "  {$r->data_ora} | {$r->matricola} | {$r->cognome} {$r->nome}\n";
}

// 3. Turni oggi
$turniN = DB::table('turni')->where('data', $oggi)->count();
echo "\nTurni in tabella per oggi: $turniN\n";

$turniSample = DB::table('turni')->where('data', $oggi)->limit(3)->get();
foreach ($turniSample as $t) {
    echo "  {$t->cognome_nome} → {$t->turno}\n";
}
