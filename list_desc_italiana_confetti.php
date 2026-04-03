<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$descrizioni = DB::table('ordini')
    ->where('cliente_nome', 'LIKE', '%ITALIANA CONFETTI%')
    ->whereNotNull('descrizione')
    ->select('commessa', 'descrizione')
    ->distinct()
    ->orderBy('descrizione')
    ->get();

echo "=== DESCRIZIONI ITALIANA CONFETTI ({$descrizioni->count()}) ===\n\n";
foreach ($descrizioni as $d) {
    echo "{$d->commessa} | {$d->descrizione}\n";
}
