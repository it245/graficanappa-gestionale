<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$exist = DB::table('sensori_ydigital')
    ->where('device_id', '207D2613CFD0')
    ->where('sensor_name', 'counter')
    ->exists();

if ($exist) {
    DB::table('sensori_ydigital')
        ->where('device_id', '207D2613CFD0')
        ->where('sensor_name', 'counter')
        ->update([
            'macchina' => 'PIEGA',
            'descrizione' => 'IR contatore piegaincolla',
            'attivo' => true,
            'updated_at' => now(),
        ]);
    echo "Sensore aggiornato (PIEGA).\n";
} else {
    DB::table('sensori_ydigital')->insert([
        'device_id'   => '207D2613CFD0',
        'sensor_name' => 'counter',
        'macchina'    => 'PIEGA',
        'descrizione' => 'IR contatore piegaincolla',
        'attivo'      => true,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
    echo "Sensore inserito (PIEGA).\n";
}
