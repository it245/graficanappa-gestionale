<?php
// Fix 66489: crea 3 ordini mancanti e riassegna PI01
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066489-26';

$ordineRef = DB::table('ordini')->where('commessa', $commessa)->first();
if (!$ordineRef) { echo "Ordine non trovato\n"; exit(1); }

echo "Ordine riferimento: #{$ordineRef->id} ({$ordineRef->cod_art} — {$ordineRef->descrizione})\n\n";

$articoliNuovi = [
    ['cod_art' => '04719.AS.ST.FI.TU.1366', 'descrizione' => 'AST.1 KG STELLATO IACCARINO'],
    ['cod_art' => '04831.AS.ST.FI.TU', 'descrizione' => "AST.1 KG TWO MILK CREMA CHANTILLY E FRAGOLINE"],
    ['cod_art' => '04843.AS.ST.FI.TU', 'descrizione' => "AST.1 KG AVOLA PENSIERO D'AMORE"],
];

// PI01 extra (stato 0, qta_prod 0) da riassegnare
$piExtra = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineRef->id)
    ->where('fase', 'PI01')
    ->where('stato', 0)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->pluck('id')
    ->toArray();

echo "PI01 extra da riassegnare: " . count($piExtra) . " (IDs: " . implode(',', $piExtra) . ")\n\n";

if (count($piExtra) < 3) {
    echo "Non ci sono abbastanza PI01 extra\n";
    exit(1);
}

foreach ($articoliNuovi as $i => $art) {
    $exists = DB::table('ordini')->where('commessa', $commessa)->where('cod_art', $art['cod_art'])->first();
    if ($exists) {
        echo "Ordine {$art['cod_art']} esiste già (#{$exists->id})\n";
        $ordineId = $exists->id;
    } else {
        $ordineId = DB::table('ordini')->insertGetId([
            'commessa' => $commessa,
            'cod_art' => $art['cod_art'],
            'descrizione' => $art['descrizione'],
            'cliente_nome' => $ordineRef->cliente_nome,
            'qta_richiesta' => 3000,
            'um' => $ordineRef->um,
            'data_registrazione' => $ordineRef->data_registrazione,
            'data_prevista_consegna' => $ordineRef->data_prevista_consegna,
            'cod_carta' => 'SEMILAVSTAMPA_FUSTELLA',
            'qta_carta' => 3000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Ordine creato: #{$ordineId} ({$art['cod_art']} — {$art['descrizione']})\n";
    }

    // Riassegna PI01
    DB::table('ordine_fasi')->where('id', $piExtra[$i])->update([
        'ordine_id' => $ordineId,
        'updated_at' => now(),
    ]);
    echo "  PI01 (ID:{$piExtra[$i]}) → ordine #{$ordineId}\n";
}

echo "\nFatto.\n";
