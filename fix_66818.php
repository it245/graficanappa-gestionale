<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// BRT1 della commessa 66818: da stato 4 a stato 2 (parziale, non totale)
DB::table('ordine_fasi')
    ->where('id', 12386) // BRT1
    ->update([
        'stato' => 2,
        'data_fine' => null,
    ]);

echo "BRT1 (ID 12386) riportata a stato 2 (consegna parziale)\n";
echo "La commessa 0066818-26 è ora visibile nella dashboard.\n";
