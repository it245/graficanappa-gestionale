<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;

$fasi = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordini.commessa', ['0067386-26', '0067381-26'])
    ->where('ordine_fasi.fase', 'like', 'STAMPAXL%')
    ->get(['ordini.commessa', 'ordine_fasi.stato', 'ordine_fasi.qta_prod',
           'ordine_fasi.fogli_buoni', 'ordine_fasi.fogli_scarto', 'ordine_fasi.data_fine', 'ordine_fasi.fase']);

foreach ($fasi as $f) {
    echo "{$f->commessa} fase={$f->fase} stato={$f->stato} qta={$f->qta_prod} buoni={$f->fogli_buoni} scarto={$f->fogli_scarto} fine={$f->data_fine}\n";
}
