<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->where('orf.fase', 'LIKE', 'STAMPA%')
    ->select('orf.id', 'orf.fase', 'orf.stato', 'orf.qta_prod', 'orf.fogli_buoni', 'orf.fogli_scarto', 'orf.updated_at')
    ->get();
foreach ($rows as $r) print_r((array)$r);
