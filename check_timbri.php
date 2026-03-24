<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$cognome = 'TORROMACCO';
$data = $argv[1] ?? date('Y-m-d', strtotime('-1 day')); // ieri di default

echo "=== TIMBRI {$cognome} — {$data} ===" . PHP_EOL;

// Cerca matricola
$anagrafica = DB::table('nettime_anagrafica')
    ->where('cognome_nome', 'LIKE', "%{$cognome}%")
    ->first();

if (!$anagrafica) {
    echo "Operatore non trovato in anagrafica" . PHP_EOL;
    exit;
}

echo "Matricola: {$anagrafica->badge_id} | {$anagrafica->cognome_nome}" . PHP_EOL . PHP_EOL;

// Cerca timbrature del giorno
$timbri = DB::table('nettime_timbrature')
    ->where('badge_id', $anagrafica->badge_id)
    ->whereDate('data_ora', $data)
    ->orderBy('data_ora')
    ->get();

echo "Timbrature trovate: " . $timbri->count() . PHP_EOL;
foreach ($timbri as $t) {
    $tipo = $t->tipo == 'E' ? 'ENTRATA' : 'USCITA';
    echo "  {$t->data_ora} | {$tipo}" . PHP_EOL;
}

// Cerca anche oggi
echo PHP_EOL . "=== OGGI (" . date('Y-m-d') . ") ===" . PHP_EOL;
$timriOggi = DB::table('nettime_timbrature')
    ->where('badge_id', $anagrafica->badge_id)
    ->whereDate('data_ora', date('Y-m-d'))
    ->orderBy('data_ora')
    ->get();

echo "Timbrature: " . $timriOggi->count() . PHP_EOL;
foreach ($timriOggi as $t) {
    $tipo = $t->tipo == 'E' ? 'ENTRATA' : 'USCITA';
    echo "  {$t->data_ora} | {$tipo}" . PHP_EOL;
}
