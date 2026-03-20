<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066816-26'))
    ->with('faseCatalogo.reparto')
    ->get();

foreach ($fasi as $f) {
    echo $f->fase
        . ' | stato:' . $f->stato
        . ' | esterno:' . ($f->esterno ? 'SI' : 'NO')
        . ' | reparto:' . ($f->faseCatalogo->reparto->nome ?? '?')
        . ' | fase_catalogo:' . ($f->faseCatalogo->nome ?? '?')
        . ' | fase_catalogo_id:' . ($f->fase_catalogo_id ?? 'NULL')
        . PHP_EOL;
}
