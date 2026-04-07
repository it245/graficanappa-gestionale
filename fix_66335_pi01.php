<?php
// Fix 66335: aggiungi 5 PI01 mancanti (Angelica, Anna, Aurora, Beatrice, Benedetta)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066335-26';

$ordine = DB::table('ordini')->where('commessa', $commessa)->first();
if (!$ordine) { echo "Ordine non trovato\n"; exit(1); }

// PI01 esistente (Alice)
$pi01 = DB::table('ordine_fasi')->where('ordine_id', $ordine->id)->where('fase', 'PI01')->first();
if (!$pi01) { echo "PI01 non trovata\n"; exit(1); }

echo "Ordine #{$ordine->id} | PI01 esistente #{$pi01->id}\n";

$nomi = ['Angelica', 'Anna', 'Aurora', 'Beatrice', 'Benedetta'];

foreach ($nomi as $nome) {
    $id = DB::table('ordine_fasi')->insertGetId([
        'ordine_id' => $ordine->id,
        'fase' => 'PI01',
        'fase_catalogo_id' => $pi01->fase_catalogo_id,
        'stato' => 0,
        'qta_fase' => 1000,
        'qta_prod' => 0,
        'priorita' => $pi01->priorita,
        'scarti_previsti' => $pi01->scarti_previsti,
        'esterno' => 0,
        'manuale' => 0,
        'note' => $nome,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  + PI01 '{$nome}' creata (#{$id})\n";
}

echo "\nFatto: 5 PI01 aggiunte\n";
