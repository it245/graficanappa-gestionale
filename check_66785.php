<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ORDINI 0066785-26 ===" . PHP_EOL;
$ordini = DB::table('ordini')->where('commessa', '0066785-26')->get();
foreach ($ordini as $o) {
    echo "ID:{$o->id} | cod_art:{$o->cod_art} | qta_carta:{$o->qta_carta} | desc:" . mb_substr($o->descrizione, 0, 80) . PHP_EOL;
}

echo PHP_EOL . "=== FASI 0066785-26 ===" . PHP_EOL;
foreach ($ordini as $o) {
    $fasi = DB::table('ordine_fasi')->where('ordine_id', $o->id)->whereNull('deleted_at')->get();
    echo "Ordine #{$o->id}:" . PHP_EOL;
    foreach ($fasi as $f) {
        echo "  id:{$f->id} | {$f->fase} | qta_fase:{$f->qta_fase} | um:{$f->um} | stato:{$f->stato}" . PHP_EOL;
    }
}
