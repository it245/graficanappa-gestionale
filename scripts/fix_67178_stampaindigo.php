<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;

$f = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0067178-26'))
    ->whereHas('faseCatalogo', fn($q) => $q->where('nome', 'STAMPAINDIGO'))
    ->first();

if (!$f) {
    echo "FASE NON TROVATA\n";
    exit(1);
}

$f->stato = '2';
$f->data_fine = null;
$f->riaperta_at = now();
$f->qta_prod_at_riapertura = $f->qta_prod;
$f->save();

echo "OK fase id={$f->id} stato=2 qta_prod={$f->qta_prod} snapshot={$f->qta_prod_at_riapertura}\n";
