<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Ordini commessa 0067386-26 ===\n";
$ords = DB::table('ordini')->where('commessa', '0067386-26')->get();
foreach ($ords as $o) {
    echo "  id={$o->id} desc='{$o->descrizione}' cliche={$o->cliche_numero} match={$o->cliche_match_type}\n";
}

echo "\nTotale ordini: " . count($ords) . "\n";
echo "Ordini con cliché 2014:\n";
foreach ($ords->where('cliche_numero', 2014) as $o) {
    echo "  - id={$o->id} desc='{$o->descrizione}'\n";
}
