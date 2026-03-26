<?php
// Fix 66335 v2: rimuovi le 5 PI01 aggiunte per errore nello stesso ordine,
// poi crea 5 ordini separati (Angelica, Anna, Aurora, Beatrice, Benedetta)
// ognuno con la propria PI01 e BRT1, con la descrizione corretta da Onda
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066335-26';

// 1. Rimuovi le 5 PI01 con note (quelle aggiunte dallo script precedente)
$ordineAlice = DB::table('ordini')->where('commessa', $commessa)->first();
if (!$ordineAlice) { echo "Ordine non trovato\n"; exit(1); }

$pi01ConNote = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineAlice->id)
    ->where('fase', 'PI01')
    ->whereNotNull('note')
    ->where('note', '!=', '')
    ->get();

echo "1. Rimuovo " . $pi01ConNote->count() . " PI01 con note (fix precedente)\n";
foreach ($pi01ConNote as $f) {
    echo "   - PI01 #{$f->id} note: {$f->note}\n";
    DB::table('ordine_fasi')->where('id', $f->id)->delete();
}

// 2. Descrizioni da Onda per i 5 nomi mancanti
$descrizioni = [
    'Angelica' => 'ASTUCCIO NOMI FS0902 Angelica STAMPA 4 COLORI + DRIP OFF (LASTRINA RISERVA FS0902 rev 2024) + ORO A CALDO + FUSTELLATURA + FINESTRATURA + INCOLLAGGIO',
    'Anna'     => 'ASTUCCIO NOMI FS0902 Anna STAMPA 4 COLORI + DRIP OFF (LASTRINA RISERVA FS0902 rev 2024) + ORO A CALDO + FUSTELLATURA + FINESTRATURA + INCOLLAGGIO',
    'Aurora'   => 'ASTUCCIO NOMI FS0902 Aurora STAMPA 4 COLORI + DRIP OFF (LASTRINA RISERVA FS0902 rev 2024) + ORO A CALDO + FUSTELLATURA + FINESTRATURA + INCOLLAGGIO',
    'Beatrice' => 'ASTUCCIO NOMI FS0902 Beatrice STAMPA 4 COLORI + DRIP OFF (LASTRINA RISERVA FS0902 rev 2024) + ORO A CALDO + FUSTELLATURA + FINESTRATURA + INCOLLAGGIO',
    'Benedetta'=> 'ASTUCCIO NOMI FS0902 Benedetta STAMPA 4 COLORI + DRIP OFF (LASTRINA RISERVA FS0902 rev 2024) + ORO A CALDO + FUSTELLATURA + FINESTRATURA + INCOLLAGGIO',
];

// PI01 di Alice (per copiare fase_catalogo_id ecc.)
$pi01Alice = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineAlice->id)
    ->where('fase', 'PI01')
    ->first();

// BRT1 di Alice
$brt1Alice = DB::table('ordine_fasi')
    ->where('ordine_id', $ordineAlice->id)
    ->where('fase', 'BRT1')
    ->first();

$brt1CatId = $brt1Alice ? $brt1Alice->fase_catalogo_id : null;

echo "\n2. Creo 5 ordini separati con PI01 + BRT1\n";

foreach ($descrizioni as $nome => $desc) {
    // Controlla se esiste già
    $esiste = DB::table('ordini')
        ->where('commessa', $commessa)
        ->where('descrizione', 'LIKE', "%{$nome}%")
        ->first();

    if ($esiste) {
        echo "   {$nome}: ordine già presente (#{$esiste->id}), skip\n";
        continue;
    }

    $nuovoId = DB::table('ordini')->insertGetId([
        'commessa' => $commessa,
        'cod_art' => $ordineAlice->cod_art,
        'descrizione' => $desc,
        'cliente_nome' => $ordineAlice->cliente_nome,
        'qta_richiesta' => 1000,
        'um' => $ordineAlice->um,
        'data_prevista_consegna' => $ordineAlice->data_prevista_consegna,
        'data_registrazione' => $ordineAlice->data_registrazione,
        'cod_carta' => $ordineAlice->cod_carta,
        'carta' => $ordineAlice->carta,
        'qta_carta' => $ordineAlice->qta_carta,
        'UM_carta' => $ordineAlice->UM_carta,
        'stato' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // PI01
    DB::table('ordine_fasi')->insert([
        'ordine_id' => $nuovoId,
        'fase' => 'PI01',
        'fase_catalogo_id' => $pi01Alice->fase_catalogo_id,
        'stato' => 0,
        'qta_fase' => 1000,
        'qta_prod' => 0,
        'priorita' => $pi01Alice->priorita,
        'scarti_previsti' => $pi01Alice->scarti_previsti,
        'esterno' => 0,
        'manuale' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // BRT1
    if ($brt1CatId) {
        DB::table('ordine_fasi')->insert([
            'ordine_id' => $nuovoId,
            'fase' => 'BRT1',
            'fase_catalogo_id' => $brt1CatId,
            'stato' => 0,
            'qta_fase' => 1000,
            'qta_prod' => 0,
            'esterno' => 0,
            'manuale' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    echo "   {$nome}: ordine #{$nuovoId} + PI01 + BRT1\n";
}

echo "\nFatto!\n";
