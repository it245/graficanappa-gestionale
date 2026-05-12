<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Fasi attive con reparto 'fustella cilindrica' raggruppate per fase + sched_macchina ===\n";
$rows = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
    ->join('reparti', 'reparti.id', '=', 'fasi_catalogo.reparto_id')
    ->whereRaw('LOWER(reparti.nome) = ?', ['fustella cilindrica'])
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', ['0', '1', '2'])
    ->selectRaw('ordine_fasi.fase, ordine_fasi.sched_macchina, COUNT(*) as n')
    ->groupBy('ordine_fasi.fase', 'ordine_fasi.sched_macchina')
    ->orderBy('ordine_fasi.fase')
    ->get();

if ($rows->isEmpty()) {
    echo "  (nessuna)\n";
} else {
    foreach ($rows as $r) {
        echo sprintf("  %-22s sched=%s : %d\n",
            $r->fase, $r->sched_macchina ?? 'NULL', $r->n);
    }
}

echo "\n=== Catalogo: fasi con reparto 'fustella cilindrica' ===\n";
$cat = DB::table('fasi_catalogo')
    ->join('reparti', 'reparti.id', '=', 'fasi_catalogo.reparto_id')
    ->whereRaw('LOWER(reparti.nome) = ?', ['fustella cilindrica'])
    ->select('fasi_catalogo.nome')
    ->get();
foreach ($cat as $c) {
    echo "  {$c->nome}\n";
}
