<?php
/**
 * Debug presenze: controlla anagrafica e matricole mancanti
 * Eseguire sul server: php check_presenze.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Anagrafiche presenti
$anag = DB::table('nettime_anagrafica')->get();
echo "=== ANAGRAFICHE ({$anag->count()}) ===" . PHP_EOL;
foreach ($anag as $a) {
    echo "  {$a->matricola} => {$a->cognome} {$a->nome}" . PHP_EOL;
}

// 2. Matricole senza anagrafica
$missing = DB::select('
    SELECT DISTINCT t.matricola
    FROM nettime_timbrature t
    LEFT JOIN nettime_anagrafica a ON t.matricola = a.matricola
    WHERE a.id IS NULL
    ORDER BY t.matricola
');
echo PHP_EOL . "=== MATRICOLE SENZA ANAGRAFICA (" . count($missing) . ") ===" . PHP_EOL;
foreach ($missing as $m) {
    echo "  {$m->matricola}" . PHP_EOL;
}

// 3. Controlla file presenze
$path = '\\\\192.168.1.253\\timbrature\\presenze.txt';
if (file_exists($path)) {
    $content = file_get_contents($path);
    echo PHP_EOL . "=== FILE PRESENZE ===" . PHP_EOL;
    echo "  Size: " . strlen($content) . " bytes" . PHP_EOL;
    // Mostra primi 500 char per debug formato
    echo "  Primi 500 char:" . PHP_EOL;
    echo substr($content, 0, 500) . PHP_EOL;
} else {
    echo PHP_EOL . "File presenze NON trovato: $path" . PHP_EOL;
}
