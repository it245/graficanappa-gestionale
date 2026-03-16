<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Rimuovi badge sbagliati dall'Excel
DB::table('nettime_anagrafica')->where('matricola', '000008')->delete();
DB::table('nettime_anagrafica')->where('matricola', '000001')->delete();
echo "Rimossi 000008 e 000001 (sbagliati)" . PHP_EOL;

// Inserisci badge corretti
DB::table('nettime_anagrafica')->updateOrInsert(
    ['matricola' => '000032'],
    ['cognome' => 'MENALE', 'nome' => 'BENITO']
);
echo "OK: 000032 → MENALE BENITO" . PHP_EOL;

DB::table('nettime_anagrafica')->updateOrInsert(
    ['matricola' => '000041'],
    ['cognome' => 'MENALE', 'nome' => 'FIORE']
);
echo "OK: 000041 → MENALE FIORE" . PHP_EOL;

$tot = DB::table('nettime_anagrafica')->count();
echo "Totale anagrafica: $tot" . PHP_EOL;
