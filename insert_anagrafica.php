<?php
/**
 * Inserisce anagrafica manuale per matricole mancanti
 * Eseguire sul server: php insert_anagrafica.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$nuovi = [
    ['matricola' => '000044', 'cognome' => 'RUSSO', 'nome' => 'MICHELE'],
    ['matricola' => '000051', 'cognome' => 'IULIANO', 'nome' => 'PASQUALE'],
    ['matricola' => '000058', 'cognome' => 'BORTONE', 'nome' => 'PAOLO'],
    ['matricola' => '000653', 'cognome' => 'GARGIULO', 'nome' => 'VINCENZO'],
    ['matricola' => '000654', 'cognome' => 'RAO', 'nome' => 'CIRO'],
    ['matricola' => '000658', 'cognome' => 'FRANCESE', 'nome' => 'FRANCESCO'],
    ['matricola' => '000660', 'cognome' => 'ZAMPELLA', 'nome' => 'ALESSANDRO'],
    ['matricola' => '000664', 'cognome' => 'CRISANTI', 'nome' => 'LORENZO'],
    ['matricola' => '000666', 'cognome' => 'TORROMACCO', 'nome' => 'GIANNANTONIO'],
    ['matricola' => '000667', 'cognome' => 'VERDE', 'nome' => 'FRANCESCO'],
    ['matricola' => '000669', 'cognome' => 'MENALE', 'nome' => 'LUIGI'],
];

foreach ($nuovi as $n) {
    DB::table('nettime_anagrafica')->updateOrInsert(
        ['matricola' => $n['matricola']],
        ['cognome' => $n['cognome'], 'nome' => $n['nome']]
    );
    echo "OK: {$n['matricola']} => {$n['cognome']} {$n['nome']}" . PHP_EOL;
}

echo PHP_EOL . "Inseriti " . count($nuovi) . " record." . PHP_EOL;

// Mostra matricole ancora senza nome
$missing = DB::select('
    SELECT DISTINCT t.matricola
    FROM nettime_timbrature t
    LEFT JOIN nettime_anagrafica a ON t.matricola = a.matricola
    WHERE a.id IS NULL
    ORDER BY t.matricola
');
if (count($missing) > 0) {
    echo PHP_EOL . "Matricole ancora senza nome:" . PHP_EOL;
    foreach ($missing as $m) {
        echo "  {$m->matricola}" . PHP_EOL;
    }
} else {
    echo PHP_EOL . "Tutte le matricole hanno un nome!" . PHP_EOL;
}
