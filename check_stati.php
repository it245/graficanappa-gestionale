<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\PrinectAttivita;

echo "=== COMMESSA 0066785-26 (STAMPA XL) ===\n";
$fasi785 = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066785-26'))
    ->where('fase', 'LIKE', 'STAMPAXL106%')
    ->get();
foreach ($fasi785 as $f) {
    $stati = ['Non iniziata', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    echo "ID: {$f->id} | Fase: {$f->fase} | Stato: {$f->stato} ({$stati[$f->stato]}) | Fogli buoni: {$f->fogli_buoni} | Data fine: {$f->data_fine}\n";
}
if ($fasi785->isEmpty()) echo "Nessuna fase trovata\n";

echo "\n=== COMMESSA 0066725-26 (STAMPA XL) ===\n";
$fasi725 = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066725-26'))
    ->where('fase', 'LIKE', 'STAMPAXL106%')
    ->get();
foreach ($fasi725 as $f) {
    $stati = ['Non iniziata', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    echo "ID: {$f->id} | Fase: {$f->fase} | Stato: {$f->stato} ({$stati[$f->stato]}) | Fogli buoni: {$f->fogli_buoni} | Data fine: {$f->data_fine}\n";

    // Mostra ultima attività Prinect
    $ultima = PrinectAttivita::where('commessa_gestionale', '0066725-26')
        ->orderByDesc('start_time')
        ->first();
    if ($ultima) {
        $minFa = \Carbon\Carbon::parse($ultima->end_time ?? $ultima->start_time)->diffInMinutes(now());
        echo "  -> Ultima attivita Prinect: {$ultima->start_time} ({$minFa} min fa)\n";
    }
}
if ($fasi725->isEmpty()) echo "Nessuna fase trovata\n";
