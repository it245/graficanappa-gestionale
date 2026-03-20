<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

echo "=== FIX DEDUP MASSIVO: allinea fasi MES a Onda ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le commesse con fasi esterno=1
$commesse = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.esterno', 1)
    ->where('ordine_fasi.stato', '<', 4)
    ->select('ordini.commessa')
    ->distinct()
    ->pluck('commessa');

$totEliminati = 0;
$totRinominati = 0;

foreach ($commesse as $commessa) {
    // Conta fasi in Onda per questa commessa (da PRDDocFasi)
    $fasiOnda = DB::connection('onda')->select("
        SELECT f.CodFase, COUNT(*) as cnt
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ?
        GROUP BY f.CodFase
    ", [$commessa]);

    $ondaCounts = [];
    foreach ($fasiOnda as $fo) {
        $ondaCounts[$fo->CodFase] = $fo->cnt;
    }

    // Fasi EXT nel MES per questa commessa
    $fasiMes = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->where('esterno', 1)
        ->where('stato', '<', 4)
        ->orderBy('id')
        ->get();

    // Raggruppa fasi MES per nome (includi anche versioni senza EXT)
    $mesPerNome = [];
    foreach ($fasiMes as $f) {
        $nome = $f->fase;
        $mesPerNome[$nome][] = $f;
    }

    foreach ($mesPerNome as $faseNome => $fasiGruppo) {
        // Cerca quante ne ha Onda (con o senza EXT)
        $countOnda = $ondaCounts[$faseNome]
            ?? $ondaCounts['EXT' . $faseNome]
            ?? 0;

        $countMes = count($fasiGruppo);

        if ($countMes <= $countOnda) continue; // OK, non ci sono duplicati

        // Troppi nel MES — elimina i duplicati (tieni i primi $countOnda)
        $daEliminare = array_slice($fasiGruppo, $countOnda);

        foreach ($daEliminare as $dup) {
            echo "ELIMINA: {$commessa} | {$dup->fase} (ID:{$dup->id}) | stato:{$dup->stato} — Onda ha {$countOnda}, MES aveva {$countMes}" . PHP_EOL;
            $dup->delete();
            $totEliminati++;
        }
    }

    // Elimina anche fasi EXT* che hanno la versione senza EXT già presente
    $fasiDopoFix = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->where('stato', '<', 4)
        ->get()
        ->groupBy('fase');

    foreach ($fasiDopoFix as $nome => $fasi) {
        if (!str_starts_with($nome, 'EXT')) continue;
        $nomeSenzaExt = substr($nome, 3);
        if (isset($fasiDopoFix[$nomeSenzaExt])) {
            // Esiste sia EXT* che versione senza EXT — elimina EXT*
            foreach ($fasi as $f) {
                if ($f->esterno || $f->stato < 3) {
                    echo "ELIMINA DUP EXT: {$commessa} | {$nome} (ID:{$f->id}) — esiste già {$nomeSenzaExt}" . PHP_EOL;
                    $f->delete();
                    $totEliminati++;
                }
            }
        }
    }

    // Ricalcola stati
    \App\Services\FaseStatoService::ricalcolaCommessa($commessa);
}

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Fasi eliminate: {$totEliminati}" . PHP_EOL;
echo "DONE" . PHP_EOL;
