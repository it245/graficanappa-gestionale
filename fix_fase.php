<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fase = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066414-26'))
    ->whereHas('faseCatalogo', fn($q) => $q->where('nome', 'like', 'STAMPAXL106%'))
    ->where('stato', 2)
    ->first();

if ($fase) {
    $fase->update(['stato' => 1]);
    $fase->operatori()->detach();
    echo "Fase {$fase->id} riportata a stato 1 e operatore rimosso\n";
} else {
    echo "Fase non trovata in stato 2\n";
}
