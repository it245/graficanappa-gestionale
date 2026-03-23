<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ordini = \App\Models\Ordine::where('commessa', '0066856-26')->with('fasi')->get();
echo "Ordini MES per 66856: " . $ordini->count() . PHP_EOL;
foreach ($ordini as $o) {
    echo PHP_EOL . "  Ordine ID:{$o->id} | Art:{$o->cod_art} | Desc:" . substr($o->descrizione ?? '', 0, 50) . PHP_EOL;
    foreach ($o->fasi as $f) {
        echo "    {$f->fase} | stato:{$f->stato} | esterno:" . ($f->esterno ? 'SI' : 'NO') . PHP_EOL;
    }
}
