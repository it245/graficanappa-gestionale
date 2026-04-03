<?php
// Fix 66548 e 66802: aggiungi fustella interni mancante
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// === 66548: aggiungi FUSTBOBST75X106 per interni (536 FG) ===
$ordine548 = DB::table('ordini')->where('commessa', '0066548-26')->first();
if ($ordine548) {
    $fustRef = DB::table('ordine_fasi')
        ->where('ordine_id', $ordine548->id)
        ->where('fase', 'FUSTBOBST75X106')
        ->whereNull('deleted_at')
        ->first();

    if ($fustRef) {
        $count = DB::table('ordine_fasi')
            ->where('ordine_id', $ordine548->id)
            ->where('fase', 'FUSTBOBST75X106')
            ->whereNull('deleted_at')
            ->count();

        if ($count < 2) {
            DB::table('ordine_fasi')->insert([
                'ordine_id' => $ordine548->id,
                'fase' => 'FUSTBOBST75X106',
                'fase_catalogo_id' => $fustRef->fase_catalogo_id,
                'stato' => 0,
                'qta_fase' => 536,
                'qta_prod' => 0,
                'esterno' => 0,
                'manuale' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "66548: FUSTBOBST75X106 interni (536 FG) creata\n";
        } else {
            echo "66548: FUSTBOBST75X106 interni gia presente\n";
        }
    }
} else {
    echo "66548: ordine non trovato\n";
}

// === 66802: aggiungi FUSTIML75X106 per interni (487 FG) all'ordine #6318 ===
$ordine802 = DB::table('ordini')->where('commessa', '0066802-26')->where('id', 6318)->first();
if (!$ordine802) {
    // Prova il secondo ordine
    $ordini802 = DB::table('ordini')->where('commessa', '0066802-26')->orderBy('id')->get();
    echo "66802: ordini trovati: " . $ordini802->count() . "\n";
    foreach ($ordini802 as $o) {
        echo "  #{$o->id} | Art:{$o->cod_art} | Qta:{$o->qta_richiesta}\n";
    }
    $ordine802 = $ordini802->last();
}

if ($ordine802) {
    $fustRef = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', '0066802-26')
        ->where('ordine_fasi.fase', 'FUSTIML75X106')
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordine_fasi.*')
        ->first();

    if ($fustRef) {
        $exists = DB::table('ordine_fasi')
            ->where('ordine_id', $ordine802->id)
            ->where('fase', 'FUSTIML75X106')
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            DB::table('ordine_fasi')->insert([
                'ordine_id' => $ordine802->id,
                'fase' => 'FUSTIML75X106',
                'fase_catalogo_id' => $fustRef->fase_catalogo_id,
                'stato' => 0,
                'qta_fase' => 487,
                'qta_prod' => 0,
                'esterno' => 0,
                'manuale' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "66802: FUSTIML75X106 interni (487 FG) creata su ordine #{$ordine802->id}\n";
        } else {
            echo "66802: FUSTIML75X106 interni gia presente\n";
        }
    } else {
        echo "66802: FUSTIML75X106 di riferimento non trovata\n";
    }
} else {
    echo "66802: ordine interni non trovato\n";
}

echo "\nFatto.\n";
