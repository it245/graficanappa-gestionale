<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0066830-26';
$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->where('o.commessa', $commessa)
    ->select('f.id', 'f.fase', 'f.stato', 'f.fogli_scarto', 'f.fogli_buoni', 'f.scarti_previsti', 'f.scarti')
    ->get();

foreach ($rows as $r) {
    echo "id={$r->id} fase={$r->fase} stato={$r->stato} fogli_scarto=" . ($r->fogli_scarto ?? 'NULL') .
         " fogli_buoni=" . ($r->fogli_buoni ?? 'NULL') .
         " scarti_previsti=" . ($r->scarti_previsti ?? 'NULL') .
         " scarti=" . ($r->scarti ?? 'NULL') . "\n";
}
