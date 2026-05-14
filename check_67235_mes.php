<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$ordini = Ordine::where('commessa', '0067235-26')->get(['id','commessa','cod_art','descrizione','qta_richiesta']);
echo "Ordini MES 67235 (" . $ordini->count() . "):\n";
foreach ($ordini as $o) {
    echo "  id={$o->id} cod_art={$o->cod_art} qta={$o->qta_richiesta} desc=" . substr($o->descrizione ?? '', 0, 80) . "\n";
}

$ids = $ordini->pluck('id')->toArray();
$fasi = OrdineFase::whereIn('ordine_id', $ids)->where('fase', 'PI01')->get(['id','ordine_id','fase','stato','qta_prod']);
echo "\nFasi PI01 (" . $fasi->count() . "):\n";
foreach ($fasi as $f) {
    echo "  id={$f->id} ordine={$f->ordine_id} stato={$f->stato} qta_prod={$f->qta_prod}\n";
}
