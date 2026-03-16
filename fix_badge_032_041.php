<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 000032 = PECORARO MARCO (ha badge nonostante "NO BADGE" nell'Excel)
DB::table('nettime_anagrafica')->updateOrInsert(
    ['matricola' => '000032'],
    ['cognome' => 'PECORARO', 'nome' => 'MARCO']
);
echo "OK: 000032 → PECORARO MARCO" . PHP_EOL;

// 000041 = MENALE FIORE (usa ancora vecchio badge, non 000001)
DB::table('nettime_anagrafica')->updateOrInsert(
    ['matricola' => '000041'],
    ['cognome' => 'MENALE', 'nome' => 'FIORE']
);
echo "OK: 000041 → MENALE FIORE" . PHP_EOL;

$tot = DB::table('nettime_anagrafica')->count();
echo "Totale anagrafica: $tot" . PHP_EOL;
