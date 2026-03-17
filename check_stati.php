<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

echo "=== COMMESSA 0066785-26 (STAMPA XL) ===\n";
$fasi785 = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066785-26'))
    ->where('fase', 'LIKE', 'STAMPAXL106%')
    ->get();
foreach ($fasi785 as $f) {
    echo "ID: {$f->id} | Fase: {$f->fase} | Stato: {$f->stato} | Fogli buoni: {$f->fogli_buoni} | Data fine: {$f->data_fine}\n";
}
if ($fasi785->isEmpty()) echo "Nessuna fase trovata\n";

echo "\n=== COMMESSA 0066725-26 (STAMPA XL) ===\n";
$fasi725 = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066725-26'))
    ->where('fase', 'LIKE', 'STAMPAXL106%')
    ->get();
foreach ($fasi725 as $f) {
    echo "ID: {$f->id} | Fase: {$f->fase} | Stato: {$f->stato} | Fogli buoni: {$f->fogli_buoni} | Data fine: {$f->data_fine}\n";
}
if ($fasi725->isEmpty()) echo "Nessuna fase trovata\n";
