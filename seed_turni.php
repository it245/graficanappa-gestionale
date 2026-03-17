<?php
/**
 * Carica turni settimanali nel DB.
 * Eseguire sul server: php seed_turni.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Crea tabella se non esiste
if (!Schema::hasTable('turni')) {
    Artisan::call('migrate', ['--force' => true]);
    echo "Tabella turni creata.\n";
}

// Settimana 16-21 marzo 2026
$giorni = ['2026-03-16', '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20', '2026-03-21'];

// Formato: [cognome_nome, [lun, mar, mer, gio, ven, sab], ora_inizio_override, ora_fine_override]
$turni = [
    // Turno unico (T = 08:00-17:00)
    ['MENALE FIORE',              ['T','T','T','T','T',null]],
    ['SCOTTI FRANCESCO',          ['T','T','T','T','T',null]],
    ['CARDILLO ANTONIO',          ['T','T','T','T','T',null]],
    ['CARDILLO MARCO',            ['T','T','T','T','T',null]],
    ['PECORARO MARCO',            ['T','T','T','T','T',null]],
    ['CRISANTI LORENZO',          ['T','T','T','T','T',null], '09:00', '18:00'], // 9-13 14-18
    ['SCARANO GIANGIUSEPPE',      ['T','T','T','T','T',null], '08:00', '17:00'], // 8-13 14-17
    ['VERDE FRANCESCO',           ['T','T','T','T','T',null]],
    ['TORROMACCO GIANNANTONIO',   ['T','T','T','T','T',null]],
    ['SORBO LUCA',                ['T','T','T','T','T',null]],
    ['D\'ORAZIO MIRKO',           ['T','T','T','T','T',null]],
    ['FRANCESE FRANCESCO',        ['T','T','T','T','T',null]],
    ['RUSSO MICHELE',             ['T','T','T','T','T',null]],
    ['SANTORO MARIO',             ['T','T','T','T','T',null]],
    ['GENNARO LA SCALA',          ['T','T','T','T','T',null]],

    // Stampa offset - turni
    ['MARFELLA DOMENICO',         ['1','1','1','1','1',null]],  // orario 1 06:00-14:00
    ['BORTONE PAOLO',             ['2','2','2','2','2',null]],  // orario 2 14:00-22:00
    ['MENALE LUIGI',              ['T','2','2','2','2',null]],
    ['MENALE BENITO',             ['1','1','R','1','1','1']],   // orario 1 06:00-14:00
    ['MARINO LUIGI',              ['2','F','F','R','1','F']],   // orario 2 14:00-22:00
    ['MORMILE COSIMO',            ['2','2','F','F','R','F']],   // orario 3 22:00-06:00
    ['CHRISTIAN SIMONETTI',       ['1','2','2','2','2','R']],
    ['VINCENZO MARRONE',          ['1','1','2','2','1','R']],   // orario S2 12:00-20:00 quando turno 2
    ['BARBATO RAFFAELE',          ['R','F','1','F','2','R']],

    // Stampa a caldo (scambio turno Pagano ↔ Gargiulo per tutta la settimana)
    ['GARGIULO VINCENZO',         ['2','2','2','2','2',null]],  // orario 2 14:00-22:00 (scambiato)
    ['PAGANO DIEGO',              ['1','1','1','1','1',null]],  // orario 1 06:00-14:00 (scambiato)

    // Altri reparti con turni
    ['MENALE FRANCESCO',          ['1','1','1','1','1',null]],  // orario 1 06:00-14:00
    ['IULIANO PASQUALE',          ['2','2','2','2','2',null], '12:00', '20:00'], // orario 2 speciale
    ['CIRO RAO',                  ['1','1','1','1','1',null]],
    ['ZAMPELLA ALESSANDRO',       ['T','R','1','1','T','1']],

    // Ferie primo giorno
    ['CASTELLANO ANTONIO',        ['F','F','T','T','T',null]],
];

$inseriti = 0;
foreach ($turni as $row) {
    $nome = $row[0];
    $schedule = $row[1];
    $oraInizio = $row[2] ?? null;
    $oraFine = $row[3] ?? null;

    foreach ($giorni as $i => $giorno) {
        $turno = $schedule[$i] ?? null;
        if (!$turno) continue; // Sabato vuoto = non lavora

        DB::table('turni')->updateOrInsert(
            ['cognome_nome' => $nome, 'data' => $giorno],
            [
                'turno' => $turno,
                'ora_inizio' => $oraInizio,
                'ora_fine' => $oraFine,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $inseriti++;
    }
}

echo "Turni caricati: $inseriti\n";

// Verifica
$totale = DB::table('turni')->count();
$settimana = DB::table('turni')->where('data', '>=', '2026-03-16')->where('data', '<=', '2026-03-21')->count();
echo "Totale turni in DB: $totale (questa settimana: $settimana)\n";

// Mostra turni di oggi
$oggi = date('Y-m-d');
echo "\nTurni di oggi ($oggi):\n";
$turniOggi = DB::table('turni')->where('data', $oggi)->orderBy('cognome_nome')->get();
foreach ($turniOggi as $t) {
    $orario = $t->ora_inizio ? "{$t->ora_inizio}-{$t->ora_fine}" : '';
    echo "  {$t->cognome_nome}: turno {$t->turno} {$orario}\n";
}
