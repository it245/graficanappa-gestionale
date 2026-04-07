<?php
// Fix 66489: aggiungi 3 PI01 mancanti all'ordine esistente
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066489-26';

$ordine = DB::table('ordini')->where('commessa', $commessa)->first();
if (!$ordine) { echo "Ordine non trovato\n"; exit(1); }

$piRef = DB::table('ordine_fasi')
    ->where('ordine_id', $ordine->id)
    ->where('fase', 'PI01')
    ->whereNull('deleted_at')
    ->first();

if (!$piRef) { echo "PI01 di riferimento non trovata\n"; exit(1); }

$piCount = DB::table('ordine_fasi')
    ->where('ordine_id', $ordine->id)
    ->where('fase', 'PI01')
    ->whereNull('deleted_at')
    ->count();

echo "Ordine #{$ordine->id} ({$ordine->cod_art})\n";
echo "PI01 esistenti: {$piCount} (servono 4)\n\n";

$creati = 0;
for ($i = $piCount; $i < 4; $i++) {
    DB::table('ordine_fasi')->insert([
        'ordine_id' => $ordine->id,
        'fase' => 'PI01',
        'fase_catalogo_id' => $piRef->fase_catalogo_id,
        'stato' => 0,
        'qta_fase' => 3000,
        'qta_prod' => 0,
        'esterno' => 0,
        'manuale' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  PI01 #" . ($i + 1) . " creata\n";
    $creati++;
}

echo "\nFasi create: {$creati}\n";
