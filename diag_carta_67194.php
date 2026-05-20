<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ordini = DB::table('ordini')->where('commessa', '0067194-26')
    ->select('id', 'cod_art', 'descrizione', 'carta', 'cod_carta', 'qta_carta', 'UM_carta')
    ->get();

echo "=== ordini 67194 ===\n";
foreach ($ordini as $o) {
    echo "id={$o->id}\n";
    echo "  cod_art:     {$o->cod_art}\n";
    echo "  descrizione: " . substr($o->descrizione ?? '', 0, 60) . "\n";
    echo "  carta:       '" . substr($o->carta ?? '', 0, 80) . "'\n";
    echo "  cod_carta:   {$o->cod_carta}\n";
    echo "  qta_carta:   {$o->qta_carta} {$o->UM_carta}\n";
    echo "\n";
}
