<?php
/**
 * Conta timbrature per giorno nel file sorgente e nel DB
 * Eseguire sul server: php check_timbrature_storico.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Conta righe per giorno nel FILE sorgente
$path = '\\\\192.168.1.253\\timbrature\\timbrature.txt';
$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "=== FILE TIMBRATURE (righe totali: " . count($lines) . ") ===" . PHP_EOL;

$perGiorno = [];
foreach ($lines as $line) {
    if (preg_match('/^R?\d{9}\s+\d{8}\s+(\d{6})\s/', $line, $m)) {
        $data = $m[1]; // ddmmyy
        $perGiorno[$data] = ($perGiorno[$data] ?? 0) + 1;
    }
}
// Ordina per data (converti ddmmyy per sort)
uksort($perGiorno, function($a, $b) {
    $da = substr($a,4,2).substr($a,2,2).substr($a,0,2);
    $db = substr($b,4,2).substr($b,2,2).substr($b,0,2);
    return strcmp($db, $da);
});

echo "Righe per giorno (ultime 14):" . PHP_EOL;
$i = 0;
foreach ($perGiorno as $data => $count) {
    if ($i++ >= 14) break;
    $giorno = substr($data,0,2).'/'.substr($data,2,2).'/20'.substr($data,4,2);
    echo "  $giorno: $count righe" . PHP_EOL;
}

// 2. Conta timbrature per giorno nel DB
echo PHP_EOL . "=== DB TIMBRATURE (ultimi 7 giorni) ===" . PHP_EOL;
$dbPerGiorno = DB::table('nettime_timbrature')
    ->select(DB::raw('DATE(data_ora) as giorno, COUNT(*) as tot, COUNT(DISTINCT matricola) as dipendenti'))
    ->where('data_ora', '>=', now()->subDays(7))
    ->groupBy('giorno')
    ->orderByDesc('giorno')
    ->get();

foreach ($dbPerGiorno as $g) {
    echo "  {$g->giorno}: {$g->tot} timbrature ({$g->dipendenti} dipendenti)" . PHP_EOL;
}

// 3. Dettaglio 12/03 nel DB
echo PHP_EOL . "=== DETTAGLIO 12/03/2026 NEL DB ===" . PHP_EOL;
$det = DB::table('nettime_timbrature')
    ->whereDate('data_ora', '2026-03-12')
    ->orderBy('matricola')
    ->orderBy('data_ora')
    ->get();

echo "Totale: {$det->count()} timbrature" . PHP_EOL;
$byMatr = $det->groupBy('matricola');
foreach ($byMatr as $matr => $timb) {
    $lista = $timb->map(fn($t) => $t->verso . date('H:i', strtotime($t->data_ora)))->implode(' ');
    echo "  $matr: $lista" . PHP_EOL;
}
