<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066623-26';

echo "=== Analisi duplicati commessa $commessa ===\n\n";

$fasi = \App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with(['faseCatalogo:id,nome', 'ordine:id,commessa,descrizione'])
    ->orderBy('priorita')
    ->get();

echo "Totale fasi: " . $fasi->count() . "\n\n";

// Raggruppa per nome fase
$grouped = $fasi->groupBy(fn($f) => ($f->faseCatalogo->nome ?? 'N/A'));
foreach ($grouped as $nome => $group) {
    $flag = $group->count() > 1 ? ' *** DUPLICATA' : '';
    echo "$nome: " . $group->count() . " fasi$flag\n";
    $byOrdine = $group->groupBy('ordine_id');
    foreach ($byOrdine as $oid => $sub) {
        $desc = mb_substr($sub->first()->ordine->descrizione ?? '?', 0, 60);
        $ids = $sub->pluck('id')->join(', ');
        echo "  ordine_id=$oid (x{$sub->count()}) ids=[$ids] desc=$desc\n";
    }
}

// Cerca duplicati veri: stesso ordine_id + stesso fase_catalogo_id
echo "\n=== DUPLICATI VERI (stesso ordine + stessa fase) ===\n";
$dupes = $fasi->groupBy(fn($f) => $f->ordine_id . '-' . $f->fase_catalogo_id)
    ->filter(fn($g) => $g->count() > 1);

if ($dupes->isEmpty()) {
    echo "Nessun duplicato vero trovato.\n";
    echo "Le fasi multiple sono su righe ordine diverse (ordine_id diversi).\n";
} else {
    foreach ($dupes as $key => $group) {
        $f = $group->first();
        $nome = $f->faseCatalogo->nome ?? '?';
        echo "ordine_id={$f->ordine_id} fase=$nome x{$group->count()} ids=[{$group->pluck('id')->join(', ')}]\n";
    }
}
