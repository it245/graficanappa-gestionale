<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$giorni = ['2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10', '2026-04-11'];

$turni = [
    'MENALE FIORE'              => ['T','T','T','T',''],
    'SCOTTI FRANCESCO'          => ['T','T','T','T',''],
    'CARDILLO ANTONIO'          => ['T','T','T','T',''],
    'CARDILLO MARCO'            => ['T','T','T','T',''],
    'PECORARO MARCO'            => ['T','T','T','T',''],
    'CRISANTI LORENZO'          => ['T','T','T','T',''],
    'SCARANO GIANGIUSEPPE'      => ['T','T','T','T',''],
    'VERDE FRANCESCO'           => ['T','T','T','T',''],
    'MARFELLA DOMENICO'         => ['2','2','2','2',''],
    'BORTONE PAOLO'             => ['1','1','1','1',''],
    'MENALE LUIGI'              => ['1','1','1','1',''],
    'MENALE FRANCESCO'          => ['2','2','2','2',''],
    'TORROMACCO GIANNANTONIO'   => ['T','T','T','T',''],
    'IULIANO PASQUALE'          => ['1','1','1','1',''],
    'CIRO RAO'                  => ['2','2','2','2',''],
    'SORBO LUCA'                => ['T','T','T','T',''],
    'CASTELLANO ANTONIO'        => ['T','T','T','T',''],
    "D'ORAZIO MIRKO"            => ['T','T','T','T',''],
    'FRANCESE FRANCESCO'        => ['T','T','T','T',''],
    'RUSSO MICHELE'             => ['T','T','T','T',''],
    'GARGIULO VINCENZO'         => ['2','2','2','2',''],
    'PAGANO DIEGO'              => ['1','1','1','1',''],
    'MENALE BENITO'             => ['1','R','1','1','2'],
    'MARINO LUIGI'              => ['2','1','R','1','1'],
    'MORMILE COSIMO'            => ['2','2','1','R','1'],
    'CHRISTIAN SIMONETTI'       => ['F','F','F','F','F'],
    'VINCENZO MARRONE'          => ['T','2','2','2','R'],
    'BARBATO RAFFAELE'          => ['1','1','2','2','R'],
    'ZAMPELLA ALESSANDRO'       => ['R','T','T','T','2'],
    'SANTORO MARIO'             => ['T','T','T','T',''],
    'GENNARO LA SCALA'          => ['T','T','T','T',''],
];

$inseriti = 0;
$aggiornati = 0;

foreach ($turni as $nome => $valori) {
    foreach ($valori as $i => $turno) {
        if ($turno === '') continue;
        $data = $giorni[$i];

        $exists = DB::table('turni')->where('cognome_nome', $nome)->where('data', $data)->exists();

        DB::table('turni')->updateOrInsert(
            ['cognome_nome' => $nome, 'data' => $data],
            ['turno' => $turno, 'updated_at' => now(), 'created_at' => now()]
        );

        if ($exists) $aggiornati++;
        else $inseriti++;
    }
}

echo "Turni settimana 07-11/04/2026: $inseriti inseriti, $aggiornati aggiornati\n";
