<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066818-26';
echo "=== COMMESSA {$commessa} — DETTAGLIO DATE ===\n\n";

$ordini = DB::table('ordini')->where('commessa', $commessa)->get();
foreach ($ordini as $o) {
    echo "Ordine ID={$o->id} Cliente={$o->cliente_nome} DataConsegna={$o->data_prevista_consegna}\n";
}

$fasi = DB::table('ordine_fasi')
    ->whereIn('ordine_id', $ordini->pluck('id'))
    ->get();

echo "\n--- Fasi con date ---\n";
foreach ($fasi as $f) {
    echo "  ID={$f->id} Fase={$f->fase} Stato={$f->stato}";
    echo " DataInizio={$f->data_inizio}";
    echo " DataFine={$f->data_fine}";
    echo " QtaProd={$f->qta_prod}";
    echo "\n";
}

echo "\nDone.\n";
