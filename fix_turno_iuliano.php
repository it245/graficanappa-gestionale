<?php
// Fix orario Iuliano Pasquale: turno 2 = 12:00-20:00 (non 14:00-22:00)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$aggiornati = DB::table('turni')
    ->where('cognome_nome', 'IULIANO PASQUALE')
    ->where('turno', '2')
    ->update([
        'ora_inizio' => '12:00',
        'ora_fine' => '20:00',
        'updated_at' => now(),
    ]);

echo "Aggiornati {$aggiornati} turni di IULIANO PASQUALE (12:00-20:00)\n";
