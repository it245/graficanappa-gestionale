<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Migra tutte le fasi esterne da stato 2 a stato 5
$fasi = DB::table('ordine_fasi')
    ->where('stato', 2)
    ->where('esterno', 1)
    ->whereNull('deleted_at')
    ->get();

echo "=== MIGRAZIONE STATO 2 → 5 PER FASI ESTERNE ===\n\n";
echo "Fasi da migrare: {$fasi->count()}\n\n";

foreach ($fasi as $f) {
    $commessa = DB::table('ordini')->where('id', $f->ordine_id)->value('commessa') ?? '?';
    echo "  ID:{$f->id} | {$commessa} | {$f->fase} | stato 2 → 5\n";
}

if ($fasi->count() > 0) {
    echo "\nEseguo aggiornamento...\n";
    $aggiornati = DB::table('ordine_fasi')
        ->where('stato', 2)
        ->where('esterno', 1)
        ->whereNull('deleted_at')
        ->update(['stato' => 5]);
    echo "Aggiornate: {$aggiornati} fasi\n";
} else {
    echo "Nessuna fase da migrare.\n";
}
