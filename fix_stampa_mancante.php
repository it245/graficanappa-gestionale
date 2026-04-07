<?php
// Fix automatico: crea STAMPA XL mancanti per ordini senza stampa
// Uso: php fix_stampa_mancante.php (trova e fixa automaticamente)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX STAMPA XL MANCANTI ===\n\n";

$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', [0, 1])
    ->distinct()
    ->pluck('ordini.commessa');

$fixati = 0;

foreach ($commesse as $commessa) {
    $articoliOnda = DB::connection('onda')->select("
        SELECT p.CodArt, f.QtaDaLavorare
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ? AND f.CodFase = 'STAMPA' AND f.CodMacchina LIKE '%XL106%'
    ", [$commessa]);

    $codArtMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
        ->whereNull('ordine_fasi.deleted_at')
        ->pluck('ordini.cod_art')
        ->unique()
        ->toArray();

    // Trova fase_catalogo_id da una STAMPA XL esistente nella commessa
    $stampaRef = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL%')
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordine_fasi.fase', 'ordine_fasi.fase_catalogo_id', 'ordine_fasi.scarti_previsti')
        ->first();

    if (!$stampaRef) continue;

    foreach ($articoliOnda as $a) {
        if (in_array($a->CodArt, $codArtMes)) continue;

        // Trova l'ordine per questo cod_art
        $ordine = DB::table('ordini')
            ->where('commessa', $commessa)
            ->where('cod_art', $a->CodArt)
            ->first();

        if (!$ordine) continue;

        // Controlla che non abbia già una STAMPA XL (soft deleted?)
        $esiste = DB::table('ordine_fasi')
            ->where('ordine_id', $ordine->id)
            ->where('fase', 'LIKE', 'STAMPAXL%')
            ->whereNull('deleted_at')
            ->exists();

        if ($esiste) continue;

        DB::table('ordine_fasi')->insert([
            'ordine_id' => $ordine->id,
            'fase' => $stampaRef->fase,
            'fase_catalogo_id' => $stampaRef->fase_catalogo_id,
            'stato' => 0,
            'qta_fase' => (int)$a->QtaDaLavorare,
            'qta_prod' => 0,
            'scarti_previsti' => $stampaRef->scarti_previsti,
            'esterno' => 0,
            'manuale' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "  {$commessa} | Art:{$a->CodArt} | STAMPA XL creata (qta:{$a->QtaDaLavorare})\n";
        $fixati++;
    }
}

echo "\nFixate: {$fixati} STAMPA XL\n";
