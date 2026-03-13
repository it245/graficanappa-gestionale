<?php
/**
 * Debug presenze: controlla anagrafica e matricole mancanti
 * Eseguire sul server: php check_presenze.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 0. Forza re-sync anagrafica
echo "=== FORZA RE-SYNC ANAGRAFICA ===" . PHP_EOL;
Artisan::call('presenze:sync');
echo Artisan::output();

// 1. Anagrafiche presenti
$anag = DB::table('nettime_anagrafica')->orderBy('cognome')->get();
echo PHP_EOL . "=== ANAGRAFICHE ({$anag->count()}) ===" . PHP_EOL;
foreach ($anag as $a) {
    echo "  [{$a->matricola}] {$a->cognome} {$a->nome}" . PHP_EOL;
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

// 3. Debug primi 3 record del file presenze per verificare larghezze campi
$path = '\\\\192.168.1.253\\timbrature\\presenze.txt';
if (file_exists($path)) {
    $content = file_get_contents($path);
    echo PHP_EOL . "=== DEBUG CAMPI FILE PRESENZE ===" . PHP_EOL;

    // Trova primi 3 record
    $count = 0;
    $offset = 0;
    while ($count < 3 && ($pos = strpos($content, 'RP', $offset)) !== false) {
        if ($pos >= 4) {
            $header = substr($content, $pos - 4, 8);
            if (preg_match('/^011[09](?:00|PR)RP$/', $header)) {
                $raw = substr($content, $pos + 4, 100);
                // Mostra hex dei primi 80 byte dopo header per capire le posizioni
                echo "  Record " . ($count + 1) . " (pos $pos):" . PHP_EOL;
                echo "    Raw (80 chars): |" . substr($raw, 0, 80) . "|" . PHP_EOL;
                // Hex dei primi 50 byte per trovare padding
                echo "    Hex (50 byte): ";
                for ($i = 0; $i < 50 && $i < strlen($raw); $i++) {
                    echo sprintf('%02X ', ord($raw[$i]));
                }
                echo PHP_EOL;
                $count++;
            }
        }
        $offset = $pos + 2;
    }
} else {
    echo PHP_EOL . "File presenze NON trovato: $path" . PHP_EOL;
}
