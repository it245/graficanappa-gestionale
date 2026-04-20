<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Tutte le fasi stampa terminate (stato 3+) senza scarti dichiarati
$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
    ->join('reparti as r', 'fc.reparto_id', '=', 'r.id')
    ->where('f.stato', '>=', 3)
    ->whereNull('f.deleted_at')
    ->whereIn('r.nome', ['stampa offset', 'digitale'])
    ->where(function ($q) {
        $q->whereNull('f.scarti')->orWhere('f.scarti', 0);
    })
    ->select('o.commessa', 'f.fase', 'f.scarti', 'f.fogli_scarto', 'f.qta_prod', 'f.data_fine',
             'r.nome as reparto')
    ->orderBy('f.data_fine', 'desc')
    ->limit(50)
    ->get();

echo "=== Fasi stampa TERMINATE (stato>=3) senza scarti reali dichiarati ===\n";
echo sprintf("%-14s %-22s %-16s %10s %10s %20s\n",
    "Commessa", "Fase", "Reparto", "Scarti", "fogli_sc", "DataFine");
echo str_repeat('-', 100) . "\n";
foreach ($rows as $r) {
    printf("%-14s %-22s %-16s %10s %10s %20s\n",
        $r->commessa, $r->fase, $r->reparto,
        $r->scarti ?? '-', $r->fogli_scarto ?? '-',
        $r->data_fine ?? '-');
}
echo "\nTotale: " . count($rows) . "\n";
