<?php
// Fix 66538: aggiungi PI01 e FIN01 mancanti (4 articoli, ne ha solo 1 per tipo)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066538-26';

// Articoli che hanno PI01 + FIN01 su Onda
$articoli = [
    '04804.AS.ST.FI.TU' => 'AST.1 KG NOISETTES NUANCE BLUE',
    '04839.AS.ST.FI.TU' => 'AST.1 KG TWO MILK CIOCOSTE\'',
    '04904.AS.ST.FI.TU' => 'AST.1 KG TWO MILK CLASSICO MIX COLORATO COMPLEANNO',
    '05023.AS.ST.FI.TU' => 'AST.1 KG NOISETTES NUANCE BORDEAUX',
];

// Prendi la FIN01 e PI01 esistenti come riferimento
$finRef = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->where('ordine_fasi.fase', 'FIN01')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordine_fasi.*')
    ->first();

$piRef = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->where('ordine_fasi.fase', 'PI01')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordine_fasi.*')
    ->first();

if (!$finRef || !$piRef) {
    echo "FIN01 o PI01 di riferimento non trovata\n";
    exit(1);
}

echo "Riferimento FIN01 ID:{$finRef->id} (ordine_id:{$finRef->ordine_id})\n";
echo "Riferimento PI01 ID:{$piRef->id} (ordine_id:{$piRef->ordine_id})\n\n";

// Trova gli ordini per ogni articolo
$creati = 0;
foreach ($articoli as $codArt => $desc) {
    $ordine = DB::table('ordini')
        ->where('commessa', $commessa)
        ->where('cod_art', $codArt)
        ->first();

    if (!$ordine) {
        echo "  Ordine per {$codArt} non trovato — skip\n";
        continue;
    }

    // Controlla se ha già FIN01
    $haFin = DB::table('ordine_fasi')
        ->where('ordine_id', $ordine->id)
        ->where('fase', 'FIN01')
        ->whereNull('deleted_at')
        ->exists();

    if (!$haFin) {
        DB::table('ordine_fasi')->insert([
            'ordine_id' => $ordine->id,
            'fase' => 'FIN01',
            'fase_catalogo_id' => $finRef->fase_catalogo_id,
            'stato' => 0,
            'qta_fase' => 2000,
            'qta_prod' => 0,
            'esterno' => 0,
            'manuale' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  FIN01 creata per {$codArt} (ordine #{$ordine->id})\n";
        $creati++;
    } else {
        echo "  FIN01 esiste per {$codArt}\n";
    }

    // Controlla se ha già PI01
    $haPi = DB::table('ordine_fasi')
        ->where('ordine_id', $ordine->id)
        ->where('fase', 'PI01')
        ->whereNull('deleted_at')
        ->exists();

    if (!$haPi) {
        DB::table('ordine_fasi')->insert([
            'ordine_id' => $ordine->id,
            'fase' => 'PI01',
            'fase_catalogo_id' => $piRef->fase_catalogo_id,
            'stato' => 0,
            'qta_fase' => 2000,
            'qta_prod' => 0,
            'esterno' => 0,
            'manuale' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  PI01 creata per {$codArt} (ordine #{$ordine->id})\n";
        $creati++;
    } else {
        echo "  PI01 esiste per {$codArt}\n";
    }
}

echo "\nFasi create: {$creati}\n";
