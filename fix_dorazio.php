<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Unifica "D ORAZIO MIRKO" → "D'ORAZIO MIRKO"
$aggiornati = DB::table('turni')
    ->where('cognome_nome', 'D ORAZIO MIRKO')
    ->update(['cognome_nome' => "D'ORAZIO MIRKO", 'updated_at' => now()]);

echo "Turni aggiornati: {$aggiornati} (D ORAZIO → D'ORAZIO)\n";
