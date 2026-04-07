<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$reparti = DB::table('reparti')->get();
echo "=== OPERATORI PER REPARTO ===" . PHP_EOL . PHP_EOL;

foreach ($reparti as $rep) {
    $operatori = DB::table('operatore_reparto')
        ->join('operatori', 'operatore_reparto.operatore_id', '=', 'operatori.id')
        ->where('operatore_reparto.reparto_id', $rep->id)
        ->where('operatori.attivo', 1)
        ->select('operatori.nome', 'operatori.cognome', 'operatori.codice_operatore')
        ->orderBy('operatori.cognome')
        ->get();

    if ($operatori->isEmpty()) continue;

    echo "--- {$rep->nome} ({$operatori->count()}) ---" . PHP_EOL;
    foreach ($operatori as $op) {
        echo "  {$op->cognome} {$op->nome} ({$op->codice_operatore})" . PHP_EOL;
    }
    echo PHP_EOL;
}
