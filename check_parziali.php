<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Commesse parziali
$parziali = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.tipo_consegna', 'parziale')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione', 'ordini.qta_richiesta',
             'ordine_fasi.id as fase_id', 'ordine_fasi.qta_fase', 'ordine_fasi.qta_prod',
             'ordine_fasi.data_fine', 'ordine_fasi.segnacollo_brt')
    ->orderBy('ordini.commessa')
    ->get();

echo "=== COMMESSE PARZIALI — DETTAGLIO QUANTITA ===\n\n";

foreach ($parziali as $p) {
    // Tutte le fasi della commessa
    $fasi = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
        ->where('ordini.commessa', $p->commessa)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.qta_fase', 'ordine_fasi.qta_prod', 'reparti.nome as reparto')
        ->get();

    $totFasi = $fasi->count();
    $terminate = $fasi->where('stato', '>=', 3)->count();
    $consegnate = $fasi->where('stato', 4)->count();

    echo "*** {$p->commessa} — {$p->cliente_nome} ***\n";
    echo "  Descrizione: " . substr($p->descrizione ?? '-', 0, 70) . "\n";
    echo "  Qta ordinata: {$p->qta_richiesta}\n";
    echo "  Consegna parziale: {$p->data_fine}\n";
    echo "  Segnacollo BRT: " . ($p->segnacollo_brt ?? '-') . "\n";
    echo "  Fasi: {$terminate}/{$totFasi} terminate, {$consegnate} consegnate\n";
    echo "  Dettaglio fasi:\n";
    foreach ($fasi as $f) {
        $stLabel = match((int)$f->stato) { 0 => 'caricato', 1 => 'pronto', 2 => 'avviato', 3 => 'terminato', 4 => 'consegnato', default => $f->stato };
        echo "    {$f->fase} ({$f->reparto}) — stato:{$stLabel} — qta:{$f->qta_fase} — prod:{$f->qta_prod}\n";
    }
    echo "\n";
}
