<?php
/**
 * Lista commesse nel MES che hanno fasi nel reparto "stampa a caldo".
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
    ->join('reparti', 'reparti.id', '=', 'fasi_catalogo.reparto_id')
    ->where('reparti.nome', 'stampa a caldo')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa')
    ->distinct()
    ->orderBy('ordini.commessa')
    ->pluck('commessa');

echo "Commesse MES con reparto 'stampa a caldo': " . $commesse->count() . "\n";
foreach ($commesse as $c) {
    echo $c . "\n";
}

// Controlla in Onda se queste commesse hanno anche FUSTBOBSTRILIEVI o RILIEVOASECCOJOH
echo "\n--- Check Onda: rilievo presente? ---\n";
$commessaBase = $commesse->map(fn($c) => preg_replace('/-\d+$/', '', $c))->unique();

foreach ($commesse as $c) {
    $base = preg_replace('/-\d+$/', '', $c);
    $fasi = DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase
        FROM PRDDocTeste p
        JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE p.CodCommessa = ?
          AND (f.CodFase LIKE '%RILIEVO%' OR f.CodFase LIKE 'FUSTBOBSTRILIEVI%')
    ", [$c]);

    $fasiNomi = collect($fasi)->pluck('CodFase')->implode(', ');
    $haRilievo = count($fasi) > 0;

    // Check se nel MES ha la fase rilievo
    $haRilievoMES = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $c)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(function ($q) {
            $q->where('ordine_fasi.fase', 'like', '%RILIEVO%')
              ->orWhere('ordine_fasi.fase', 'like', 'FUSTBOBSTRILIEVI%');
        })
        ->exists();

    if ($haRilievo && !$haRilievoMES) {
        echo "  {$c} → MANCA nel MES! Onda ha: {$fasiNomi}\n";
    } elseif ($haRilievo && $haRilievoMES) {
        echo "  {$c} → OK (Onda: {$fasiNomi}, presente nel MES)\n";
    } else {
        echo "  {$c} → Nessun rilievo in Onda\n";
    }
}
