<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$primaDesc = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', '0067235-26')
    ->where('ordine_fasi.fase', 'PI01')
    ->orderBy('ordine_fasi.id')
    ->value('ordine_fasi.descrizione_fase');

$ordine = Ordine::find(8961);
echo "PRIMA: " . substr($ordine->descrizione, 0, 80) . "\n";
$ordine->descrizione = $primaDesc;
$ordine->save();
echo "DOPO:  " . substr($ordine->descrizione, 0, 80) . "\n";
