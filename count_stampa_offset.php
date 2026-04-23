<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$q1 = OrdineFase::where('stato', 3)
    ->whereHas('faseCatalogo.reparto', fn($q) => $q->whereRaw('LOWER(nome) = ?', ['stampa offset']))
    ->with('ordine:id,commessa')
    ->get()
    ->pluck('ordine.commessa')
    ->filter()
    ->unique();

echo "Via reparto: " . $q1->count() . "\n";

$q2 = OrdineFase::where('stato', 3)
    ->where('fase', 'like', 'STAMPAXL106%')
    ->with('ordine:id,commessa')
    ->get()
    ->pluck('ordine.commessa')
    ->filter()
    ->unique();

echo "Via fase STAMPAXL106*: " . $q2->count() . "\n";

$q3 = OrdineFase::where('stato', 3)
    ->where(function($q) {
        $q->whereHas('faseCatalogo.reparto', fn($r) => $r->whereRaw('LOWER(nome) = ?', ['stampa offset']))
          ->orWhere('fase', 'like', 'STAMPAXL106%')
          ->orWhere('fase', 'like', 'STAMPA%OFFSET%');
    })
    ->with('ordine:id,commessa')
    ->get()
    ->pluck('ordine.commessa')
    ->filter()
    ->unique();

echo "Query completa: " . $q3->count() . "\n";
