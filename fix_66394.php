<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$ordini = Ordine::where('commessa', '0066394-26')->get();
foreach ($ordini as $o) {
    $fasi = OrdineFase::where('ordine_id', $o->id)->get();
    foreach ($fasi as $f) {
        $f->operatori()->detach();
        $f->delete();
    }
    $o->delete();
    echo "Eliminato ordine #{$o->id}: {$o->descrizione}\n";
}
echo "Totale: " . count($ordini) . " ordini eliminati\n";
