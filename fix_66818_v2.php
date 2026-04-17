<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// BRT1 della commessa 66818: setta tipo_consegna = parziale
DB::table('ordine_fasi')
    ->where('id', 12386)
    ->update([
        'tipo_consegna' => 'parziale',
    ]);

echo "BRT1 (ID 12386) → tipo_consegna = parziale\n";
echo "Ora visibile in 'Consegne parziali' della dashboard spedizione.\n";
