<?php
/**
 * Popola tabella turni per settimana 2026-04-20 (lun) - 2026-04-25 (sab).
 * Dati dall'immagine WhatsApp foglio turni.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$giorni = ['2026-04-20', '2026-04-21', '2026-04-22', '2026-04-23', '2026-04-24', '2026-04-25'];

// [nome_intero, turni6giorni, orainizio override|null, orafine override|null]
$dati = [
    ['MENALE FIORE',          ['T','T','T','T','T',null], null, null],
    ['SCOTTI FRANCESCO',      ['T','T','T','T','T',null], null, null],
    ['CARDILLO ANTONIO',      ['T','T','T','T','T',null], null, null],
    ['CARDILLO MARCO',        ['T','T','T','T','T',null], null, null],
    ['PECORARO MARCO',        ['T','T','T','T','T',null], null, null],
    ['CRISANTI LORENZO',      ['T','T','T','T','T',null], '09:00', '18:00'], // 9-13 14-18
    ['SCARANO GIANGIUSEPPE',  ['T','T','T','T','T',null], '08:00', '17:00'], // 8-13 14-17
    ['VERDE FRANCESCO',       ['T','T','T','T','T',null], null, null],
    ['MARFELLA DOMENICO',     ['2','2','2','2','F',null], null, null],
    ['BORTONE PAOLO',         ['1','1','1','1','1',null], null, null],
    ['GENNARO LA SCALA',      ['1','1','1','1','1',null], null, null],
    ['MENALE LUIGI',          ['1','1','1','1','1',null], null, null],
    ['MENALE FRANCESCO',      ['2','2','2','2','2',null], null, null],
    ['TORROMACCO GIANNANT',   ['T','T','T','T','T',null], '06:00', '14:00'], // orario 1 speciale
    ['IULIANO PASQUALE',      ['2','1','1','1','1',null], '12:00', '20:00'], // orario 2 speciale 12-20
    ['CIRO RAO',              ['1','2','2','2','2',null], null, null],
    ['SORBO LUCA',            ['T','F','T','T','F',null], null, null],
    ['CASTELLANO ANTONIO',    ['T','T','T','T','F',null], null, null],
    ["D'ORAZIO MIRKO",        ['T','T','T','T','T',null], null, null],
    ['FRANCESE FRANCESCO',    ['T','T','T','T','T',null], null, null],
    ['RUSSO MICHELE',         ['T','T','T','T','T',null], null, null],
    ['GARGIULO VINCENZO',     ['F','2','2','2','2',null], null, null],
    ['PAGANO DIEGO',          ['T','1','1','1','1',null], null, null],
    ['MENALE BENITO',         ['1','1','2','2','2','R'], null, null],
    ['MARINO LUIGI',          ['R','1','1','2','2','R'], null, null],
    ['MORMILE COSIMO',        ['T','R','1','1','2','2'], null, null],
    ['CHRISTIAN SIMONETTI',   ['T','3','R','1','1','2'], null, null],
    ['VINCENZO MARRONE',      ['2','3','3','R','1','1'], null, null],
    ['BARBATO RAFFAELE',      ['2','2','3','3','R','1'], null, null],
    ['ZAMPELLA ALESSANDRO',   ['1','2','2','3','R','R'], null, null],
    ['SANTORO MARIO',         ['T','T','T','T','T',null], null, null],
];

$inseriti = 0;
$aggiornati = 0;
foreach ($dati as [$nome, $turni, $oraIni, $oraFin]) {
    foreach ($turni as $i => $turno) {
        if ($turno === null || $turno === '') continue;
        $data = $giorni[$i];
        $res = DB::table('turni')->updateOrInsert(
            ['cognome_nome' => $nome, 'data' => $data],
            [
                'turno' => $turno,
                'ora_inizio' => $oraIni,
                'ora_fine' => $oraFin,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        // updateOrInsert non torna count, contiamo separato
        $exists = DB::table('turni')->where('cognome_nome', $nome)->where('data', $data)->exists();
        if ($exists) $inseriti++;
    }
}

echo "Righe turni scritte per settimana 20-25/04/2026: $inseriti\n";
echo "Dipendenti: " . count($dati) . "\n";
echo "Done.\n";
