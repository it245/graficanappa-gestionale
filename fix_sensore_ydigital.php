<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$sensori = DB::table('sensori_ydigital')->get();
echo "Sensori configurati: " . count($sensori) . "\n";
foreach ($sensori as $s) {
    echo "  id={$s->id} device={$s->device_id} sensor_name=[{$s->sensor_name}] macchina={$s->macchina} attivo={$s->attivo}\n";
}

$aggiornati = DB::table('sensori_ydigital')
    ->where('device_id', '207D2613CFD0')
    ->update(['sensor_name' => 'BreakBeam_1']);

echo "\nAggiornati: $aggiornati\n";

$sensori = DB::table('sensori_ydigital')->get();
foreach ($sensori as $s) {
    echo "DOPO: id={$s->id} device={$s->device_id} sensor_name=[{$s->sensor_name}]\n";
}
