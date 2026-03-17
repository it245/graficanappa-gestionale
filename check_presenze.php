<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$oggi = date('Y-m-d');
echo "=== DEBUG PRESENZE - $oggi ===\n\n";

// Timbrature di oggi
$oggi_count = DB::table('nettime_timbrature')->whereDate('data_ora', $oggi)->count();
echo "Timbrature oggi: $oggi_count\n";

if ($oggi_count > 0) {
    echo "\nTimbrature di oggi:\n";
    $timb = DB::table('nettime_timbrature')->whereDate('data_ora', $oggi)->orderBy('data_ora')->get();
    foreach ($timb as $t) {
        $anag = DB::table('nettime_anagrafica')->where('matricola', $t->matricola)->first();
        $nome = $anag ? "{$anag->cognome} {$anag->nome}" : "Matr. {$t->matricola}";
        echo "  {$t->data_ora} | {$t->verso} | {$nome}\n";
    }
}

// Ultime 10 timbrature in assoluto
echo "\nUltime 10 timbrature nel DB:\n";
$ultime = DB::table('nettime_timbrature')->orderByDesc('data_ora')->limit(10)->get();
foreach ($ultime as $t) {
    $anag = DB::table('nettime_anagrafica')->where('matricola', $t->matricola)->first();
    $nome = $anag ? "{$anag->cognome} {$anag->nome}" : "Matr. {$t->matricola}";
    echo "  {$t->data_ora} | {$t->verso} | {$nome}\n";
}

// Totale e range
$stats = DB::table('nettime_timbrature')->selectRaw('COUNT(*) as tot, MIN(data_ora) as prima, MAX(data_ora) as ultima')->first();
echo "\nTotale timbrature: {$stats->tot}\n";
echo "Range: {$stats->prima} -> {$stats->ultima}\n";

// Anagrafica
$anag_count = DB::table('nettime_anagrafica')->count();
echo "Dipendenti in anagrafica: $anag_count\n";

// Ultime 3 righe del file raw per vedere se oggi c'è
$path = '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP';
if (file_exists($path)) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $last5 = array_slice($lines, -5);
    echo "\nUltime 5 righe del file TIMBRACP.BKP:\n";
    foreach ($last5 as $l) {
        echo "  $l\n";
    }
} else {
    echo "\nFile TIMBRACP.BKP non raggiungibile\n";
}
