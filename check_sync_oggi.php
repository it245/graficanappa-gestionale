<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
echo "=== ORDINI CREATI OGGI ($oggi) ===\n\n";

$ordini = App\Models\Ordine::whereDate('created_at', $oggi)->orderBy('commessa')->get();
echo "Totale: " . $ordini->count() . "\n\n";

$perCommessa = $ordini->groupBy('commessa');
echo "Commesse uniche: " . $perCommessa->count() . "\n\n";

// Commesse con più ordini (potenziali duplicati)
$duplicati = $perCommessa->filter(fn($g) => $g->count() > 1);
if ($duplicati->isNotEmpty()) {
    echo "--- COMMESSE CON PIU' ORDINI (possibili duplicati) ---\n";
    foreach ($duplicati as $comm => $group) {
        echo "  $comm ({$group->count()} ordini) - {$group->first()->cliente_nome}\n";
        foreach ($group as $o) {
            echo "    ID:{$o->id} | " . substr($o->descrizione, 0, 50) . "\n";
        }
    }
    echo "\n";
}

// Lista commesse singole
echo "--- TUTTE LE COMMESSE ---\n";
foreach ($perCommessa as $comm => $group) {
    echo "  $comm ({$group->count()}) | {$group->first()->cliente_nome} | " . substr($group->first()->descrizione, 0, 40) . "\n";
}
