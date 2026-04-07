<?php
// Controlla pivot fase_operatore per una commessa/fase
// Uso: php check_pivot.php 66709 PLAOPA1LATO
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66709';
$fase = $argv[2] ?? null;

$commessa = str_pad($cerca, 7, '0', STR_PAD_LEFT) . '-26';
if (strpos($cerca, '-') !== false) $commessa = $cerca;

echo "=== PIVOT fase_operatore per {$commessa}" . ($fase ? " fase {$fase}" : "") . " ===\n\n";

$query = DB::table('fase_operatore')
    ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
    ->where('ordini.commessa', $commessa);

if ($fase) {
    $query->where('ordine_fasi.fase', 'LIKE', "%{$fase}%");
}

$results = $query->select(
    'ordine_fasi.fase', 'ordine_fasi.stato as fase_stato',
    'ordine_fasi.data_inizio as fase_inizio', 'ordine_fasi.data_fine as fase_fine',
    'operatori.nome', 'operatori.cognome',
    'fase_operatore.data_inizio as pivot_inizio',
    'fase_operatore.data_fine as pivot_fine',
    'fase_operatore.secondi_pausa'
)->get();

foreach ($results as $r) {
    echo "  {$r->fase} | stato:{$r->fase_stato}\n";
    echo "    Fase: inizio:{$r->fase_inizio} → fine:" . ($r->fase_fine ?? 'NULL') . "\n";
    echo "    Pivot {$r->nome} {$r->cognome}: inizio:{$r->pivot_inizio} → fine:" . ($r->pivot_fine ?? 'NULL') . " | pausa:{$r->secondi_pausa}s\n\n";
}

if ($results->isEmpty()) echo "  Nessun record trovato\n";
