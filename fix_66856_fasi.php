<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$ordineId = 3626; // BROSSURATI.OFFSET (rivista)

echo "=== AGGIUNGI FASI MANCANTI A ORDINE 3626 (rivista 66856) ===" . PHP_EOL;

// STAMPAXL106.1 — dalla macchina non specificata, usiamo offset
$repartoStampa = Reparto::where('nome', 'stampa offset')->first();
$catStampa = FasiCatalogo::where('nome', 'STAMPAXL106.1')->first();

if ($catStampa && !OrdineFase::where('ordine_id', $ordineId)->where('fase_catalogo_id', $catStampa->id)->exists()) {
    OrdineFase::create([
        'ordine_id' => $ordineId,
        'fase' => 'STAMPAXL106.1',
        'fase_catalogo_id' => $catStampa->id,
        'qta_fase' => 8827,
        'um' => 'FG',
        'priorita' => config('fasi_priorita')['STAMPAXL106.1'] ?? 10,
        'stato' => 0,
    ]);
    echo "CREATA: STAMPAXL106.1 | qta:8827" . PHP_EOL;
} else {
    echo "STAMPAXL106.1 già esiste o catalogo mancante" . PHP_EOL;
}

// BRT1
$catBrt = FasiCatalogo::where('nome', 'BRT1')->first();
if ($catBrt && !OrdineFase::where('ordine_id', $ordineId)->where('fase_catalogo_id', $catBrt->id)->exists()) {
    OrdineFase::create([
        'ordine_id' => $ordineId,
        'fase' => 'BRT1',
        'fase_catalogo_id' => $catBrt->id,
        'qta_fase' => 447,
        'um' => 'KG',
        'priorita' => config('fasi_priorita')['BRT1'] ?? 96,
        'stato' => 0,
    ]);
    echo "CREATA: BRT1 | qta:447" . PHP_EOL;
} else {
    echo "BRT1 già esiste o catalogo mancante" . PHP_EOL;
}

\App\Services\FaseStatoService::ricalcolaCommessa('0066856-26');
echo PHP_EOL . "DONE — ricalcolato" . PHP_EOL;
