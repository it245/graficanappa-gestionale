<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ordini = App\Models\Ordine::where('commessa', 'like', '%0066363%')->get();
echo "Trovati: " . $ordini->count() . " ordini\n\n";

foreach ($ordini as $o) {
    echo "COMMESSA: {$o->commessa}\n";
    echo "Cliente: {$o->cliente_nome}\n";
    echo "Descrizione: {$o->descrizione}\n";
    echo "Stato: {$o->stato}\n";
    echo "Qta carta: {$o->qta_carta}\n";
    echo "Consegna: {$o->data_prevista_consegna}\n\n";

    $fasi = $o->fasi()->with('faseCatalogo.reparto', 'operatori')->get();
    foreach ($fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        $stato = ['In attesa', 'Pronta', 'In lavorazione', 'Terminata', 'Consegnata'][$f->stato] ?? $f->stato;
        $ops = $f->operatori->pluck('nome')->join(', ') ?: '-';
        echo "  {$f->fase} ({$rep}) | Stato: {$stato} | Esterno: " . ($f->esterno ? 'si' : 'no') . " | Op: {$ops}\n";
    }
    echo "\n";
}
