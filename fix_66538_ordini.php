<?php
// Fix 66538: crea 3 ordini mancanti e riassegna FIN01+PI01
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066538-26';

// Ordine di riferimento (l'unico nel MES)
$ordineRef = DB::table('ordini')->where('commessa', $commessa)->where('cod_art', '05023.AS.ST.FI.TU')->first();
if (!$ordineRef) { echo "Ordine riferimento non trovato\n"; exit(1); }

echo "Ordine riferimento: #{$ordineRef->id} ({$ordineRef->cod_art})\n\n";

// Articoli da creare
$articoliNuovi = [
    ['cod_art' => '04804.AS.ST.FI.TU', 'descrizione' => 'AST.1 KG NOISETTES NUANCE BLUE'],
    ['cod_art' => '04839.AS.ST.FI.TU', 'descrizione' => "AST.1 KG TWO MILK CIOCOSTE'"],
    ['cod_art' => '04904.AS.ST.FI.TU', 'descrizione' => 'AST.1 KG TWO MILK CLASSICO MIX COLORATO COMPLEANNO'],
];

// Prendi le FIN01 e PI01 extra (stato 0, qta_prod 0) da riassegnare
$finExtra = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineRef->id)
    ->where('fase', 'FIN01')
    ->where('stato', 0)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->pluck('id')
    ->toArray();

$piExtra = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineRef->id)
    ->where('fase', 'PI01')
    ->where('stato', 0)
    ->whereNull('deleted_at')
    ->orderBy('id')
    ->pluck('id')
    ->toArray();

echo "FIN01 extra da riassegnare: " . count($finExtra) . " (IDs: " . implode(',', $finExtra) . ")\n";
echo "PI01 extra da riassegnare: " . count($piExtra) . " (IDs: " . implode(',', $piExtra) . ")\n\n";

if (count($finExtra) < 3 || count($piExtra) < 3) {
    echo "Non ci sono abbastanza fasi extra da riassegnare\n";
    exit(1);
}

foreach ($articoliNuovi as $i => $art) {
    // Controlla se l'ordine esiste già
    $exists = DB::table('ordini')->where('commessa', $commessa)->where('cod_art', $art['cod_art'])->first();
    if ($exists) {
        echo "Ordine {$art['cod_art']} esiste già (#{$exists->id}) — riassegno fasi\n";
        $ordineId = $exists->id;
    } else {
        // Crea ordine copiando i campi dal riferimento
        $ordineId = DB::table('ordini')->insertGetId([
            'commessa' => $commessa,
            'cod_art' => $art['cod_art'],
            'descrizione' => $art['descrizione'],
            'cliente_nome' => $ordineRef->cliente_nome,
            'qta_richiesta' => 2000,
            'um' => $ordineRef->um,
            'data_registrazione' => $ordineRef->data_registrazione,
            'data_prevista_consegna' => $ordineRef->data_prevista_consegna,
            'cod_carta' => 'SEMILAVSTAMPA_FUSTELLA',
            'qta_carta' => 2000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Ordine creato: #{$ordineId} ({$art['cod_art']} — {$art['descrizione']})\n";
    }

    // Riassegna FIN01
    DB::table('ordine_fasi')->where('id', $finExtra[$i])->update([
        'ordine_id' => $ordineId,
        'updated_at' => now(),
    ]);
    echo "  FIN01 (ID:{$finExtra[$i]}) → ordine #{$ordineId}\n";

    // Riassegna PI01
    DB::table('ordine_fasi')->where('id', $piExtra[$i])->update([
        'ordine_id' => $ordineId,
        'updated_at' => now(),
    ]);
    echo "  PI01 (ID:{$piExtra[$i]}) → ordine #{$ordineId}\n";
}

// BRT1: 1 sola per commessa (dedup), già presente sull'ordine Bordeaux

echo "\nFatto.\n";
