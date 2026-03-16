<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$righe = DB::table('nettime_timbrature')
    ->join('nettime_anagrafica', 'nettime_timbrature.matricola', '=', 'nettime_anagrafica.matricola')
    ->select(
        'nettime_timbrature.matricola',
        'nettime_anagrafica.cognome',
        'nettime_anagrafica.nome',
        DB::raw('COUNT(*) as cnt'),
        DB::raw('MAX(data_ora) as ultima')
    )
    ->where('data_ora', '>=', '2026-03-01')
    ->groupBy('nettime_timbrature.matricola', 'nettime_anagrafica.cognome', 'nettime_anagrafica.nome')
    ->orderBy('nettime_anagrafica.cognome')
    ->orderBy('cnt', 'desc')
    ->get();

echo str_pad('BADGE', 8) . str_pad('COGNOME', 20) . str_pad('NOME', 20) . str_pad('TIMB', 6) . "ULTIMA\n";
echo str_repeat('-', 80) . "\n";

foreach ($righe as $r) {
    echo str_pad($r->matricola, 8)
        . str_pad($r->cognome, 20)
        . str_pad($r->nome, 20)
        . str_pad($r->cnt, 6)
        . $r->ultima . "\n";
}
