<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$commessa = '0066475-26';
echo "=== COMMESSA $commessa ===\n\n";

$ordini = Ordine::where('commessa', $commessa)->get();
echo "Ordini trovati: " . $ordini->count() . "\n";
foreach ($ordini as $o) {
    echo "  ID: {$o->id} | Desc: " . substr($o->descrizione, 0, 60) . " | Qta: {$o->qta_richiesta}\n";
}

echo "\n=== FASI ===\n";
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with(['faseCatalogo.reparto', 'ordine'])
    ->orderBy('priorita')
    ->get();

echo "Totale fasi: " . $fasi->count() . "\n\n";
foreach ($fasi as $f) {
    $stati = ['Non iniziata', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    $reparto = $f->faseCatalogo->reparto->nome ?? '-';
    $desc = substr($f->ordine->descrizione ?? '-', 0, 40);
    $statoLabel = isset($stati[$f->stato]) ? $stati[$f->stato] : '?';
    $ext = $f->esterno ? 'SI' : 'no';
    echo "  ID:{$f->id} | {$f->fase} | Reparto: {$reparto} | Stato: {$f->stato} ({$statoLabel}) | Esterno: {$ext} | Desc: {$desc}\n";
}
