<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;

$f = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0067178-26'))
    ->whereHas('faseCatalogo', fn($q) => $q->where('nome', 'STAMPAINDIGO'))
    ->with('ordine')
    ->first();

if (!$f) {
    echo "FASE NON TROVATA\n";
    exit(1);
}

$snapshot = (int)($f->qta_prod_at_riapertura ?? 0);
$qtaProd = (int)($f->qta_prod ?? 0);
$delta = max(0, $qtaProd - $snapshot);
$qtaRich = (int)($f->ordine->qta_richiesta ?? 0);
$qtaFase = (int)($f->qta_fase ?? 0);
$qtaCarta = (int)($f->ordine->qta_carta ?? 0);
$target = $qtaRich ?: ($qtaFase ?: $qtaCarta);

echo "=== Fase STAMPAINDIGO 0067178-26 ===\n";
echo "id: {$f->id}\n";
echo "stato: " . var_export($f->stato, true) . "\n";
echo "qta_prod: {$qtaProd}\n";
echo "qta_prod_at_riapertura (snapshot): {$snapshot}\n";
echo "delta: {$delta}\n";
echo "riaperta_at: " . ($f->riaperta_at ?? 'NULL') . "\n";
echo "data_inizio: " . ($f->data_inizio ?? 'NULL') . "\n";
echo "data_fine: " . ($f->data_fine ?? 'NULL') . "\n";
echo "terminata_manualmente: " . var_export($f->terminata_manualmente, true) . "\n";
echo "--- Target check ---\n";
echo "qta_richiesta: {$qtaRich}\n";
echo "qta_fase: {$qtaFase}\n";
echo "qta_carta: {$qtaCarta}\n";
echo "target effettivo: {$target}\n";
echo "delta vs target: {$delta} >= {$target} ? " . ($target > 0 && $delta >= $target ? 'SI -> termine' : 'NO -> sicuro') . "\n";
