<?php
/**
 * Aggiorna anagrafica badge completa da Excel compilato da Marco
 * Eseguire su .60: php aggiorna_anagrafica_completa.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Svuota anagrafica e ricrea da zero con i dati corretti
echo "=== PULIZIA ANAGRAFICA ===" . PHP_EOL;
$vecchi = DB::table('nettime_anagrafica')->count();
DB::table('nettime_anagrafica')->truncate();
echo "Rimossi $vecchi record vecchi." . PHP_EOL;

// Anagrafica corretta da Marco (badge → cognome, nome)
$anagrafica = [
    '000659' => ['AMOROSO', 'DOMENICO'],
    '000059' => ['BARBATO', 'RAFFAELE'],
    '000058' => ['BORTONE', 'PAOLO'],
    '000002' => ['CARDILLO', 'ANTONIO'],
    '000045' => ['CARDILLO', 'MARCO'],
    '000014' => ['CASTELLANO', 'ANTONIO'],
    '000664' => ['CRISANTI', 'LORENZO'],
    '000651' => ['D ORAZIO', 'MIRKO'],
    '000658' => ['FRANCESE', 'FRANCESCO'],
    '000653' => ['GARGIULO', 'VINCENZO'],
    '000051' => ['IULIANO', 'PASQUALE'],
    '000025' => ['MARFELLA', 'DOMENICO'],
    '000023' => ['MARINO', 'LUIGI'],
    '000668' => ['MARRONE', 'VINCENZO'],
    '000008' => ['MENALE', 'BENITO'],
    '000001' => ['MENALE', 'FIORE'],
    '000007' => ['MENALE', 'FRANCESCO'],
    '000669' => ['MENALE', 'LUIGI'],
    '000016' => ['MORMILE', 'COSIMO'],
    '000662' => ['PAGANO', 'DIEGO'],
    '000654' => ['RAO', 'CIRO'],
    '000044' => ['RUSSO', 'MICHELE'],
    '000009' => ['SANTORO', 'MARIO'],
    '000039' => ['SCARANO', 'GIANGIUSEPPE'],
    '000019' => ['SCOTTI', 'FRANCESCO'],
    '000024' => ['SORBO', 'LUCA'],
    '000666' => ['TORROMACCO', 'GIANNANTONIO'],
    '000667' => ['VERDE', 'FRANCESCO'],
    '000660' => ['ZAMPELLA', 'ALESSANDRO'],
];

echo PHP_EOL . "=== INSERIMENTO ANAGRAFICA CORRETTA ===" . PHP_EOL;
foreach ($anagrafica as $badge => [$cognome, $nome]) {
    DB::table('nettime_anagrafica')->insert([
        'matricola' => $badge,
        'cognome' => $cognome,
        'nome' => $nome,
    ]);
    echo "  $badge → $cognome $nome" . PHP_EOL;
}

echo PHP_EOL . "=== MERGE TIMBRATURE VECCHI BADGE ===" . PHP_EOL;

// Mappa: vecchio badge → nuovo badge (dove il badge fisico è cambiato)
// Le timbrature con il vecchio badge vanno spostate sul nuovo
$merge = [
    // CARDILLO ANTONIO: vecchio 000054 → nuovo 000002
    '000054' => '000002',
    // CASTELLANO ANTONIO: vecchio 000021 → nuovo 000014
    '000021' => '000014',
    // MENALE BENITO: vecchio 000014 → nuovo 000008
    // ATTENZIONE: 000014 ora è CASTELLANO! Prima sposta 000014→000008, poi 000021→000014
    // Ordine: prima MENALE BENITO (014→008), poi CASTELLANO (021→014)
    // Ma questo crea conflitto. Facciamo in ordine corretto:
    // I vecchi badge che non corrispondono a nessun badge attivo vanno spostati

    // MENALE FIORE: vecchio 000041 → nuovo 000001
    '000041' => '000001',
    // MENALE FRANCESCO: prima non aveva associazione, ora è 000007 (già in uso con nome sbagliato)
    // MORMILE COSIMO: vecchio 000019 → nuovo 000016
    '000019' => '000016', // Ma 000019 ora è SCOTTI! Conflitto.
    // MARINO LUIGI: vecchio 000025 → nuovo 000023
    '000025' => '000023', // Ma 000025 ora è MARFELLA!
    // SCOTTI FRANCESCO: vecchio 000023 → nuovo 000019
    '000023' => '000019', // Ma 000023 ora è MARINO!
    // SORBO LUCA: vecchio 000027 → nuovo 000024
    '000027' => '000024',
    // PAGANO DIEGO: vecchio 000024 → nuovo 000662
    '000024' => '000662', // Ma 000024 ora è SORBO!
];

// Troppi conflitti circolari con merge diretto.
// Approccio: NON fare merge. Le timbrature vecchie restano con il vecchio badge
// che ora non è più in anagrafica → appariranno come "Matricola XXXXXX".
// Solo le nuove timbrature (da oggi in poi) saranno associate correttamente.

echo "  Le timbrature storiche con vecchi badge resteranno non associate." . PHP_EOL;
echo "  Solo le nuove timbrature saranno corrette." . PHP_EOL;

// Esclusi dal badge (no badge o pensione):
echo PHP_EOL . "=== ESCLUSI ===" . PHP_EOL;
echo "  PECORARO MARCO: NO BADGE (matricola 45)" . PHP_EOL;
echo "  SIMONETTI CHRISTIAN: NO BADGE (matricola 81)" . PHP_EOL;
echo "  TORROMACCO GIUSEPPE: PENSIONE" . PHP_EOL;

$tot = DB::table('nettime_anagrafica')->count();
echo PHP_EOL . "=== TOTALE: $tot dipendenti con badge ===" . PHP_EOL;
