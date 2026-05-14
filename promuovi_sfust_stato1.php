<?php
/**
 * Promuovi fasi SFUST stato 0 → 1 sulle commesse dove almeno
 * un altro ordine ha già fase SFUST a stato 1 (pronta).
 * Mantiene allineamento multi-modello.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$faseList = ['SFUST','SFUST.IML.FUSTELLATO'];

// Commesse che hanno ALMENO una fase SFUST a stato 1
$commesseAttive = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.fase', $faseList)
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '1')
    ->pluck('ordini.commessa')
    ->unique()
    ->values();

echo "Commesse SFUST con almeno 1 fase a stato 1: " . $commesseAttive->count() . "\n";

$totPromosse = 0;
foreach ($commesseAttive as $comm) {
    $promosse = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $comm)
        ->whereIn('ordine_fasi.fase', $faseList)
        ->whereNull('ordine_fasi.deleted_at')
        ->where('ordine_fasi.stato', '0')
        ->update(['ordine_fasi.stato' => '1']);

    if ($promosse > 0) {
        echo "  $comm: $promosse fasi 0→1\n";
        $totPromosse += $promosse;
    }
}

echo "\nTotale: $totPromosse fasi promosse.\n";
