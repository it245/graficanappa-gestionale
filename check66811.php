<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = App\Models\OrdineFase::whereHas('ordine', function($q) {
    $q->where('commessa', '0066811-26');
})->with('faseCatalogo')->get();

echo "=== FASI COMMESSA 66811 ===" . PHP_EOL;
foreach ($fasi as $f) {
    echo $f->fase
        . ' | stato: ' . $f->stato
        . ' | fogli_buoni: ' . ($f->fogli_buoni ?? 0)
        . ' | fogli_scarto: ' . ($f->fogli_scarto ?? 0)
        . ' | inizio: ' . ($f->data_inizio ?? '-')
        . ' | fine: ' . ($f->data_fine ?? '-')
        . PHP_EOL;
}
