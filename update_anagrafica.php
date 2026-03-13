<?php
/**
 * Aggiorna anagrafica badge mancanti + rimuovi vecchi duplicati
 * Eseguire su .60: php update_anagrafica.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Nuove associazioni badge
$nuovi = [
    ['matricola' => '000039', 'cognome' => 'SCARANO', 'nome' => 'GIANGIUSEPPE'],
    ['matricola' => '000045', 'cognome' => 'CARDILLO', 'nome' => 'MARCO'],
    ['matricola' => '000059', 'cognome' => 'BARBATO', 'nome' => 'RAFFAELE'],
    ['matricola' => '000651', 'cognome' => 'D ORAZIO', 'nome' => 'MIRKO'],
    ['matricola' => '000662', 'cognome' => 'PAGANO', 'nome' => 'DIEGO'],
    ['matricola' => '000668', 'cognome' => 'MARRONE', 'nome' => 'VINCENZO'],
];

foreach ($nuovi as $n) {
    DB::table('nettime_anagrafica')->updateOrInsert(
        ['matricola' => $n['matricola']],
        ['cognome' => $n['cognome'], 'nome' => $n['nome']]
    );
    echo "OK: {$n['matricola']} → {$n['cognome']} {$n['nome']}\n";
}

echo "\nFatto! 6 badge aggiornati.\n";
