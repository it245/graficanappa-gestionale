<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

// Fasi STAMPA XL terminate il 19/03/2026 tra 12:30 e 12:40
$fasi = OrdineFase::with('ordine')
    ->where(function ($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    ->where('stato', '>=', 3)
    ->where('data_fine', '>=', '2026-03-19 12:30:00')
    ->where('data_fine', '<=', '2026-03-19 12:42:00')
    ->get();

echo "=== FASI STAMPA XL TERMINATE IL 19/03/2026 ~12:36 ===" . PHP_EOL;
echo "Trovate: " . $fasi->count() . PHP_EOL . PHP_EOL;

foreach ($fasi as $f) {
    $commessa = $f->ordine->commessa ?? '?';
    $cliente = $f->ordine->cliente_nome ?? '?';
    echo "Commessa: {$commessa} | Cliente: {$cliente}" . PHP_EOL;
    echo "  Fase: {$f->fase} | Stato: {$f->stato}" . PHP_EOL;
    echo "  Fogli buoni: {$f->fogli_buoni} | Fogli scarto: {$f->fogli_scarto}" . PHP_EOL;
    echo "  Data inizio: " . ($f->data_inizio ?? 'NULL') . PHP_EOL;
    echo "  Data fine:   " . ($f->data_fine ?? 'NULL') . PHP_EOL;

    // Attività Prinect per questa commessa
    $attivita = \App\Models\PrinectAttivita::where('commessa_gestionale', $commessa)
        ->orderByDesc('start_time')
        ->limit(3)
        ->get();
    echo "  Ultime attività Prinect:" . PHP_EOL;
    foreach ($attivita as $a) {
        echo "    {$a->start_time} | fogli: {$a->fogli_buoni} | operatore: {$a->operatore}" . PHP_EOL;
    }
    echo PHP_EOL;
}
