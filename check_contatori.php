<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ContatoreStampante;

$ultimi = ContatoreStampante::orderByDesc('created_at')->limit(10)->get();
echo "Ultimi 10 snapshot contatori:\n";
foreach ($ultimi as $c) {
    echo "  {$c->created_at} totale={$c->totale_1} nero_g={$c->nero_grande} colore_g={$c->colore_grande}\n";
}
echo "\nTotale record: " . ContatoreStampante::count() . "\n";
