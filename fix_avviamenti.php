<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$r = App\Models\Reparto::where('nome', 'esterno')->first();
$f = App\Models\FasiCatalogo::where('nome', 'AVVIAMENTISTAMPA.EST1.1')->first();

if ($f && $r) {
    $f->reparto_id = $r->id;
    $f->save();
    echo "Aggiornato: reparto_id={$r->id}\n";
} else {
    echo "Non trovato\n";
}
