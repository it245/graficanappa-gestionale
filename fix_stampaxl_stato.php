<?php
// Riporta a stato 0 le fasi STAMPAXL106* che sono a stato 1 (promosse erroneamente)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$updated = OrdineFase::where('fase', 'like', 'STAMPAXL106%')
    ->where('stato', 1)
    ->update(['stato' => 0]);

echo "Fasi STAMPAXL106 riportate da stato 1 a stato 0: $updated\n";
