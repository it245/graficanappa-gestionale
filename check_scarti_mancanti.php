<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Fasi STAMPA XL terminate (stato 3) senza scarti reali ===\n\n";

$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->join('fasi_catalogo as fc', 'fc.id', '=', 'f.fase_catalogo_id')
    ->join('reparti as r', 'r.id', '=', 'fc.reparto_id')
    ->whereRaw('LOWER(r.nome) = ?', ['stampa offset'])
    ->where('f.stato', '3')
    ->whereNull('f.deleted_at')
    ->where(function ($q) {
        $q->whereNull('f.scarti')->orWhere('f.scarti', 0);
    })
    ->where('f.qta_prod', '>', 0)
    ->select(
        'o.commessa',
        'o.cliente_nome',
        'o.descrizione',
        'f.fase',
        'f.qta_prod',
        'f.scarti',
        'f.scarti_previsti',
        'f.data_fine'
    )
    ->orderBy('f.data_fine', 'desc')
    ->limit(100)
    ->get();

echo "Totale: " . count($rows) . "\n\n";
echo sprintf("%-13s %-30s %-12s %8s %8s %s\n",
    'commessa', 'cliente', 'fase', 'qta_prod', 'sc_prev', 'data_fine');
echo str_repeat('-', 105) . "\n";
foreach ($rows as $r) {
    echo sprintf("%-13s %-30s %-12s %8s %8s %s\n",
        $r->commessa,
        substr($r->cliente_nome ?? '-', 0, 30),
        $r->fase,
        $r->qta_prod,
        $r->scarti_previsti ?? '-',
        substr($r->data_fine ?? '-', 0, 16)
    );
}

echo "\nTotale fasi con scarti reali mancanti: " . count($rows) . "\n";
