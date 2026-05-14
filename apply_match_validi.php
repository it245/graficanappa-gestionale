<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;

$matches = [
    8551 => 2058,  // SPOSA NOVELLA
    8554 => 2054,  // DOLCE SPOSA
    8562 => 2054,
    8557 => 2204,  // BON BON CREAM SFUMATO
    8563 => 2204,
    8564 => 2204,
    10809 => 2187, // NUANCE WAVE
    10812 => 2187,
    10857 => 2187, // NUANCE GARDEN
    10838 => 2056, // 40 PLUS
];

foreach ($matches as $ordineId => $cliche) {
    $o = Ordine::find($ordineId);
    if (!$o) { echo "Ord $ordineId non trovato\n"; continue; }
    $o->cliche_numero = $cliche;
    $o->cliche_match_type = 'manual';
    $o->cliche_matched_at = now();
    $o->save();
    echo "Ord $ordineId ({$o->commessa}) " . substr($o->descrizione, 0, 50) . " → cliché $cliche\n";
}
echo "\nFatto.\n";
