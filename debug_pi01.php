<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$flusso = config('fasi_priorita');
$fasi = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066721-26'))->get();
$piOrdine = $flusso['PI01'] ?? 500;

echo "PI01 priorita flusso: {$piOrdine}" . PHP_EOL . PHP_EOL;

foreach ($fasi as $f) {
    $priof = $flusso[$f->fase] ?? 500;
    $pred = $priof < $piOrdine ? 'PREDECESSORE' : '-';
    $statoNum = is_numeric($f->stato) ? $f->stato : "PAUSA({$f->stato})";
    echo "{$f->fase} | stato:{$statoNum} | ordine_id:{$f->ordine_id} | flusso:{$priof} | {$pred}" . PHP_EOL;
}
