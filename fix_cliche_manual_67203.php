<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;

$mapping = [
    'TWO MILK CLASSICO BIANCO' => 2022,  // gia auto-matched
    'DOLCE SPOSA' => 2054,                // LE CLASSICHE DOLCE SPOSA
    'SPOSA NOVELLA' => 2058,              // LE CLASSICHE SPOSA NOVELLA
    'LES NOISETTES SFUMATE ROSA' => 2180, // LES NOISETTES SFUMATO ROSA
];

$ordini = Ordine::where('commessa', '0067203-26')->get();
foreach ($ordini as $o) {
    $desc = strtoupper($o->descrizione ?? '');
    foreach ($mapping as $needle => $cliche) {
        if (stripos($desc, $needle) !== false) {
            if ($o->cliche_numero == $cliche) {
                echo "  ordine {$o->id} gia OK ({$cliche})\n";
                break;
            }
            $o->cliche_numero = $cliche;
            $o->cliche_match_type = 'manual';
            $o->cliche_matched_at = now();
            $o->save();
            echo "  ordine {$o->id} ({$needle}) -> cliche {$cliche}\n";
            break;
        }
    }
}
echo "\nFatto.\n";
