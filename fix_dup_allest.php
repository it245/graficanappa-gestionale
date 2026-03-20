<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

// Trova tutte le commesse con duplicati: sia Allest.Manuale che EXTAllest.Manuale
$commesse = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.fase', 'LIKE', '%Allest.Manuale%')
    ->select('ordini.commessa')
    ->groupBy('ordini.commessa')
    ->havingRaw('COUNT(DISTINCT ordine_fasi.fase) > 1')
    ->pluck('commessa');

echo "=== FIX DUPLICATI Allest.Manuale / EXTAllest.Manuale ===" . PHP_EOL;
echo "Commesse con duplicati: " . $commesse->count() . PHP_EOL . PHP_EOL;

foreach ($commesse as $c) {
    $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $c))
        ->where('fase', 'LIKE', '%Allest.Manuale%')
        ->get();

    echo "{$c}:" . PHP_EOL;
    foreach ($fasi as $f) {
        echo "  ID:{$f->id} | {$f->fase} | cat_id:{$f->fase_catalogo_id} | stato:{$f->stato}" . PHP_EOL;
    }

    // Tieni la fase senza EXT, elimina quella con EXT
    $daEliminare = $fasi->filter(fn($f) => str_starts_with($f->fase, 'EXT'));
    foreach ($daEliminare as $f) {
        echo "  → ELIMINA ID:{$f->id} ({$f->fase})" . PHP_EOL;
        $f->delete();
    }

    // Se ci sono duplicati della stessa fase (es. 2x Allest.Manuale), tieni solo la più recente
    $senzaExt = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $c))
        ->where('fase', 'Allest.Manuale')
        ->orderByDesc('id')
        ->get();

    if ($senzaExt->count() > 1) {
        foreach ($senzaExt->skip(1) as $dup) {
            echo "  → ELIMINA DUPLICATO ID:{$dup->id} ({$dup->fase})" . PHP_EOL;
            $dup->delete();
        }
    }

    \App\Services\FaseStatoService::ricalcolaCommessa($c);
    echo "  Ricalcolato" . PHP_EOL . PHP_EOL;
}

echo "DONE" . PHP_EOL;
