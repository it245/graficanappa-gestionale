<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', '0067386-26')
    ->where('ordine_fasi.fase', 'like', 'STAMPAXL106%')
    ->select(
        'ordine_fasi.id',
        'ordine_fasi.fase',
        'ordine_fasi.stato',
        'ordine_fasi.qta_prod',
        'ordine_fasi.fogli_buoni',
        'ordine_fasi.fogli_scarto',
        'ordine_fasi.data_inizio',
        'ordine_fasi.data_fine'
    )
    ->get()
    ->toArray();

print_r($rows);
