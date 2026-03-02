<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fase = App\Models\OrdineFase::with('ordine')
    ->whereHas('ordine', fn($q) => $q->where('commessa', '0066587-26'))
    ->where('fase', 'LIKE', 'STAMP%')
    ->first();

if (!$fase) {
    echo "Fase STAMPA non trovata per 0066587-26\n";
    exit;
}

echo "fase={$fase->fase}\n";
echo "qta_prod={$fase->qta_prod}\n";
echo "qta_fase={$fase->qta_fase}\n";
echo "fogli_buoni={$fase->fogli_buoni}\n";
echo "qta_carta=" . ($fase->ordine->qta_carta ?? 'null') . "\n";
echo "scarti_previsti=" . ($fase->scarti_previsti ?? 'null') . "\n";
echo "stato={$fase->stato}\n";
echo "fase_id={$fase->id}\n";
