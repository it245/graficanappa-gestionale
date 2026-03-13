<?php
/**
 * Inserisce anagrafica badge nuovi e rimuove vecchi duplicati
 * Eseguire sul server: php insert_anagrafica.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Badge nuovi da inserire
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

// Badge vecchi da eliminare (stesse persone, matricola vecchia)
$vecchi = [
    '000048', // RUSSO MICHELE → ora 000044
    '000052', // IULIANO PASQUALE → ora 000051
    '000061', // BORTONE PAOLO → ora 000058
    '000050', // GARGIULO VINCENZO → ora 000653
    '000038', // RAO CIRO → ora 000654
    '000017', // FRANCESE FRANCESCO → ora 000658
    '000040', // ZAMPELLA ALESSANDRO → ora 000660
    '000016', // CRISANTI LORENZO → ora 000664
    '000060', // TORROMACCO GIANNANTONIO → ora 000666
    '000035', // VERDE FRANCESCO → ora 000667
    '000018', // MENALE LUIGI → ora 000669
];

echo "=== INSERIMENTO BADGE NUOVI ===" . PHP_EOL;
foreach ($nuovi as $n) {
    DB::table('nettime_anagrafica')->updateOrInsert(
        ['matricola' => $n['matricola']],
        ['cognome' => $n['cognome'], 'nome' => $n['nome']]
    );
    echo "  OK: {$n['matricola']} => {$n['cognome']} {$n['nome']}" . PHP_EOL;
}

echo PHP_EOL . "=== ELIMINAZIONE BADGE VECCHI ===" . PHP_EOL;
foreach ($vecchi as $v) {
    $anag = DB::table('nettime_anagrafica')->where('matricola', $v)->first();
    if ($anag) {
        DB::table('nettime_anagrafica')->where('matricola', $v)->delete();
        echo "  RIMOSSO: {$v} ({$anag->cognome} {$anag->nome})" . PHP_EOL;
    } else {
        echo "  SKIP: {$v} (non trovato)" . PHP_EOL;
    }
}

// Riepilogo
$totale = DB::table('nettime_anagrafica')->count();
echo PHP_EOL . "Anagrafica totale: $totale record" . PHP_EOL;

// Matricole ancora senza nome
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
