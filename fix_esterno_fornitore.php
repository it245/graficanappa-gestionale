<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$fasi = OrdineFase::where('stato', '<', 4)
    ->where(fn($q) => $q->where('esterno', 0)->orWhereNull('esterno'))
    ->where('note', 'LIKE', '%Inviato a:%')
    ->with('ordine')
    ->get();

echo "Fasi con fornitore ma esterno=0: " . $fasi->count() . PHP_EOL;

foreach ($fasi as $f) {
    $f->esterno = 1;
    $f->save();
    echo "FIX: {$f->ordine->commessa ?? '?'} | {$f->fase} → esterno:SI" . PHP_EOL;
}

echo "DONE" . PHP_EOL;
