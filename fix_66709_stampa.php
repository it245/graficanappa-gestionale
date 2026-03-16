<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Riapri la STAMPA XL della 66709 (era stata auto-terminata erroneamente)
$updated = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', '0066709-26')
    ->where('ordine_fasi.fase', 'like', 'STAMPAXL106%')
    ->where('ordine_fasi.stato', 3)
    ->whereNull('ordine_fasi.deleted_at')
    ->update([
        'ordine_fasi.stato' => 2,
        'ordine_fasi.data_fine' => null,
    ]);

echo "66709: $updated fasi STAMPA riaperte (stato 3 → 2)" . PHP_EOL;
