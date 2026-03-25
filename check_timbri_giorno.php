<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$data = $argv[1] ?? '2026-03-23';

echo "=== TIMBRATURE GREZZE {$data} ===" . PHP_EOL;

$anagrafica = DB::table('nettime_anagrafica')->orderBy('cognome')->get();
$timbrature = DB::table('nettime_timbrature')
    ->whereDate('data_ora', $data)
    ->orderBy('matricola')
    ->orderBy('data_ora')
    ->get()
    ->groupBy('matricola');

echo "Matricole con timbrature: " . $timbrature->count() . PHP_EOL;
echo "Anagrafica totale: " . $anagrafica->count() . PHP_EOL . PHP_EOL;

// Chi ha timbrato
foreach ($anagrafica as $a) {
    $timbri = $timbrature[$a->matricola] ?? collect();
    $nome = "{$a->cognome} {$a->nome}";

    if ($timbri->isEmpty()) continue;

    echo "{$nome} ({$a->matricola}):" . PHP_EOL;
    foreach ($timbri as $t) {
        $tipo = $t->verso == 'E' ? 'ENTRATA' : 'USCITA';
        $ora = date('H:i:s', strtotime($t->data_ora));
        echo "  {$ora} {$tipo}" . PHP_EOL;
    }
    echo PHP_EOL;
}

// Chi NON ha timbrato
echo "=== NON HANNO TIMBRATO ===" . PHP_EOL;
foreach ($anagrafica as $a) {
    if (!isset($timbrature[$a->matricola]) || $timbrature[$a->matricola]->isEmpty()) {
        echo "  {$a->cognome} {$a->nome} ({$a->matricola})" . PHP_EOL;
    }
}
