<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== sched_macchina per fasi STEL attive ===\n";
$rows = DB::table('ordine_fasi')
    ->whereIn('fase', ['FUSTSTELG33.44', 'FUSTSTELP25.35'])
    ->whereNull('deleted_at')
    ->whereIn('stato', ['0', '1', '2'])
    ->selectRaw('fase, sched_macchina, COUNT(*) as n')
    ->groupBy('fase', 'sched_macchina')
    ->get();
foreach ($rows as $r) {
    echo "  {$r->fase} -> sched_macchina='" . ($r->sched_macchina ?? 'NULL') . "' : {$r->n} fasi\n";
}

echo "\n=== Reparto fasi STEL attive ===\n";
$reparti = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'fasi_catalogo.fase', '=', 'ordine_fasi.fase')
    ->whereIn('ordine_fasi.fase', ['FUSTSTELG33.44', 'FUSTSTELP25.35'])
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', ['0', '1', '2'])
    ->selectRaw('ordine_fasi.fase, fasi_catalogo.reparto, COUNT(*) as n')
    ->groupBy('ordine_fasi.fase', 'fasi_catalogo.reparto')
    ->get();
foreach ($reparti as $r) {
    echo "  {$r->fase} -> reparto='{$r->reparto}' : {$r->n} fasi\n";
}

echo "\n=== Fasi su BOBST col reparto cilindrica ===\n";
$rows2 = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'fasi_catalogo.fase', '=', 'ordine_fasi.fase')
    ->where('ordine_fasi.sched_macchina', 'BOBST')
    ->where('fasi_catalogo.reparto', 'like', '%cilindrica%')
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', ['0', '1', '2'])
    ->selectRaw('ordine_fasi.fase, COUNT(*) as n')
    ->groupBy('ordine_fasi.fase')
    ->get();
foreach ($rows2 as $r) {
    echo "  {$r->fase} : {$r->n}\n";
}
if ($rows2->isEmpty()) echo "  (nessuna)\n";
