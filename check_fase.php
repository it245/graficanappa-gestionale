<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$faseId = $argv[1] ?? 7786;

$fase = \App\Models\OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->find($faseId);

if (!$fase) {
    echo "Fase $faseId non trovata\n";
    exit(1);
}

echo "=== Fase id=$faseId ===\n";
echo "Commessa: " . ($fase->ordine->commessa ?? '?') . "\n";
echo "Fase: " . ($fase->faseCatalogo->nome ?? $fase->fase) . "\n";
echo "Stato: {$fase->stato}\n";
echo "Qta fase: {$fase->qta_fase} | Qta prodotta: {$fase->qta_prod}\n";
echo "Data inizio: {$fase->data_inizio}\n";
echo "Data fine: {$fase->data_fine}\n";
echo "Operatori:\n";
foreach ($fase->operatori as $op) {
    echo "  {$op->nome} {$op->cognome} - inizio: {$op->pivot->data_inizio} fine: {$op->pivot->data_fine} pausa: {$op->pivot->secondi_pausa}s\n";
}

// Tutte le fasi della stessa commessa
echo "\n=== Tutte le fasi della commessa ===\n";
$tutteFasi = \App\Models\OrdineFase::where('ordine_id', $fase->ordine_id)
    ->with('faseCatalogo')
    ->orderBy('priorita')
    ->get();

foreach ($tutteFasi as $f) {
    $nome = $f->faseCatalogo->nome ?? $f->fase;
    echo sprintf("  id=%-6d stato=%d prio=%-6s fase=%-25s qta_prod=%-5s data_fine=%s\n",
        $f->id, $f->stato, $f->priorita, $nome, $f->qta_prod ?? '-', $f->data_fine ?? '-');
}
