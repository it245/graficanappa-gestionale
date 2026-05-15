<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Reset STAMPAINDIGOBN (errore mio fix precedente)
DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->where('orf.fase', 'STAMPAINDIGOBN')
    ->update([
        'orf.qta_prod' => 0,
        'orf.fogli_buoni' => 0,
        'orf.fogli_scarto' => 0,
    ]);

// STAMPA XL → 6192 (max F/R) + 2423 scarto
DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->where(function($q) {
        $q->where('orf.fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('orf.fase', 'STAMPA')
          ->orWhere('orf.fase', 'LIKE', 'STAMPA XL%');
    })
    ->update([
        'orf.qta_prod' => 6192,
        'orf.fogli_buoni' => 6192,
        'orf.fogli_scarto' => 2423,
    ]);

// Verifica
$fasi = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->select('orf.fase', 'orf.stato', 'orf.qta_prod', 'orf.fogli_buoni', 'orf.fogli_scarto')
    ->get();
foreach ($fasi as $f) {
    echo "  {$f->fase} stato={$f->stato} qta={$f->qta_prod} buoni={$f->fogli_buoni} scarto={$f->fogli_scarto}\n";
}
