<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$operatori = [
    ['matricola' => '000670', 'cognome' => 'LA SCALA', 'nome' => 'GENNARO'],
    ['matricola' => '000671', 'cognome' => 'MARCONE', 'nome' => 'EMANUELE'],
    ['matricola' => '000672', 'cognome' => 'PIETROPAOLO', 'nome' => 'GIOVANNI'],
    ['matricola' => '000673', 'cognome' => 'SIMONETTI', 'nome' => 'CHRISTIAN'],
];

foreach ($operatori as $op) {
    DB::table('nettime_anagrafica')->updateOrInsert(
        ['matricola' => $op['matricola']],
        $op
    );
    echo "Aggiunto/aggiornato: {$op['matricola']} {$op['cognome']} {$op['nome']}\n";
}

echo "\nDone.\n";
