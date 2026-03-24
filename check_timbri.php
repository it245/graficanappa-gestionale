<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$cognome = $argv[1] ?? 'TORROMACCO';
$data = $argv[2] ?? date('Y-m-d', strtotime('-1 day'));

echo "=== TIMBRI {$cognome} ===" . PHP_EOL;

// Cerca matricola
$anagrafica = DB::table('nettime_anagrafica')
    ->where('cognome', 'LIKE', "%{$cognome}%")
    ->first();

if (!$anagrafica) {
    echo "Non trovato in anagrafica. Cerco per nome..." . PHP_EOL;
    $anagrafica = DB::table('nettime_anagrafica')
        ->where('nome', 'LIKE', "%{$cognome}%")
        ->first();
}

if (!$anagrafica) {
    echo "Operatore non trovato." . PHP_EOL;
    echo PHP_EOL . "Anagrafica disponibile:" . PHP_EOL;
    $tutti = DB::table('nettime_anagrafica')->get();
    foreach ($tutti as $a) {
        echo "  Matricola:{$a->matricola} | {$a->cognome} {$a->nome}" . PHP_EOL;
    }
    exit;
}

echo "Matricola: {$anagrafica->matricola} | {$anagrafica->cognome} {$anagrafica->nome}" . PHP_EOL;

// Ieri
echo PHP_EOL . "=== {$data} (ieri) ===" . PHP_EOL;
$timbri = DB::table('nettime_timbrature')
    ->where('matricola', $anagrafica->matricola)
    ->whereDate('data_ora', $data)
    ->orderBy('data_ora')
    ->get();

echo "Timbrature: " . $timbri->count() . PHP_EOL;
foreach ($timbri as $t) {
    $tipo = $t->verso == 'E' ? 'ENTRATA' : ($t->verso == 'U' ? 'USCITA' : $t->verso);
    echo "  {$t->data_ora} | {$tipo}" . PHP_EOL;
}

// Oggi
echo PHP_EOL . "=== " . date('Y-m-d') . " (oggi) ===" . PHP_EOL;
$oggi = DB::table('nettime_timbrature')
    ->where('matricola', $anagrafica->matricola)
    ->whereDate('data_ora', date('Y-m-d'))
    ->orderBy('data_ora')
    ->get();

echo "Timbrature: " . $oggi->count() . PHP_EOL;
foreach ($oggi as $t) {
    $tipo = $t->verso == 'E' ? 'ENTRATA' : ($t->verso == 'U' ? 'USCITA' : $t->verso);
    echo "  {$t->data_ora} | {$tipo}" . PHP_EOL;
}
