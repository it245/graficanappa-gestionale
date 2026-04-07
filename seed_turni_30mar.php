<?php
// Inserisce turni settimana 30/03 - 04/04 2026
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$date = ['2026-03-30','2026-03-31','2026-04-01','2026-04-02','2026-04-03','2026-04-04'];

$turni = [
    //                              lun   mar   mer   gio   ven   sab
    'MENALE FIORE'             => ['T',  'T',  'T',  'T',  'T',  null],
    'SCOTTI FRANCESCO'         => ['T',  'T',  'T',  'F',  'T',  null],
    'CARDILLO ANTONIO'         => ['T',  'T',  'T',  'T',  null, null],
    'CARDILLO MARCO'           => ['T',  'T',  'T',  'T',  null, null],
    'PECORARO MARCO'           => ['T',  'T',  'T',  'T',  null, null],
    'CRISANTI LORENZO'         => ['T',  'T',  'T',  'T',  'T',  'T'],
    'SCARANO GIANGIUSEPPE'     => ['T',  'T',  'T',  'T',  'T',  'T'],
    'VERDE FRANCESCO'          => ['T',  'T',  'T',  'T',  'T',  null],
    'MARFELLA DOMENICO'        => ['1',  '1',  '1',  '1',  '1',  null],
    'BORTONE PAOLO'            => ['2',  '2',  '2',  '2',  '2',  null],
    'MENALE LUIGI'             => ['2',  '2',  '2',  '2',  '2',  null],
    'MENALE FRANCESCO'         => ['1',  '1',  '1',  '1',  '1',  null],
    'TORROMACCO GIANNANTONIO'  => ['1',  '1',  '1',  '1',  '1',  null],
    'IULIANO PASQUALE'         => ['2',  '2',  '2',  '2',  '2',  null],
    'CIRO RAO'                 => ['T',  'F',  'T',  'T',  'T',  null],
    'SORBO LUCA'               => ['T',  'T',  'T',  'T',  'T',  null],
    'CASTELLANO ANTONIO'       => ['F',  'T',  'T',  'T',  'T',  null],
    'D ORAZIO MIRKO'           => ['T',  'T',  'T',  'T',  'T',  null],
    'FRANCESE FRANCESCO'       => ['T',  'T',  'T',  'T',  'T',  null],
    'RUSSO MICHELE'            => ['T',  'T',  'T',  'T',  'T',  null],
    'GARGIULO VINCENZO'        => ['1',  '1',  '1',  '1',  '1',  null],
    'PAGANO DIEGO'             => ['2',  '2',  '2',  '2',  '2',  null],
    'MENALE BENITO'            => ['1',  '1',  '2',  '1',  '1',  'R'],
    'MARINO LUIGI'             => ['1',  '1',  '1',  '1',  'T',  'R'],
    'MORMILE COSIMO'           => ['R',  '1',  '1',  '1',  '2',  'R'],
    'CHRISTIAN SIMONETTI'      => ['F',  'F',  'F',  'F',  'F',  'F'],
    'VINCENZO MARRONE'         => ['1',  '2',  'R',  '1',  '2',  'R'],
    'BARBATO RAFFAELE'         => ['2',  'T',  'R',  '1',  '1',  'R'],
    'ZAMPELLA ALESSANDRO'      => ['1',  '1',  'T',  'T',  'R',  'R'],
    'SANTORO MARIO'            => ['T',  'T',  'T',  'T',  'T',  null],
    'GENNARO LA SCALA'         => ['T',  'T',  'T',  'T',  'T',  null],
];

// Override orari per sabato
$overrideSabato = [
    'CRISANTI LORENZO'     => ['09:00', '18:00'],  // 9-13 14-18
    'SCARANO GIANGIUSEPPE' => ['08:00', '17:00'],  // 8-13 14-17
];

$inseriti = 0;
$aggiornati = 0;

foreach ($turni as $nome => $settimana) {
    foreach ($settimana as $i => $turno) {
        if ($turno === null) continue;

        $data = $date[$i];
        $oraInizio = null;
        $oraFine = null;

        // Override sabato
        if ($i === 5 && isset($overrideSabato[$nome])) {
            $oraInizio = $overrideSabato[$nome][0];
            $oraFine = $overrideSabato[$nome][1];
        }

        $existing = DB::table('turni')
            ->where('cognome_nome', $nome)
            ->where('data', $data)
            ->first();

        if ($existing) {
            DB::table('turni')->where('id', $existing->id)->update([
                'turno' => $turno,
                'ora_inizio' => $oraInizio,
                'ora_fine' => $oraFine,
                'updated_at' => now(),
            ]);
            $aggiornati++;
        } else {
            DB::table('turni')->insert([
                'cognome_nome' => $nome,
                'data' => $data,
                'turno' => $turno,
                'ora_inizio' => $oraInizio,
                'ora_fine' => $oraFine,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inseriti++;
        }
    }
}

echo "Turni inseriti: {$inseriti}\n";
echo "Turni aggiornati: {$aggiornati}\n";
echo "Periodo: 30/03 - 04/04 2026\n";
