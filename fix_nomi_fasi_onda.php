<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;

echo "=== FIX: rinomina fasi MES al nome originale Onda ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Fasi rinominate: nome MES senza EXT → nome Onda con EXT
$rinominate = [
    'Allest.Manuale' => 'EXTAllest.Manuale',
    'ALLEST.SHOPPER' => 'EXTALLEST.SHOPPER',
    'ALLEST.SHOPPER024' => 'EXTALLEST.SHOPPER024',
    'ALLESTIMENTO.CALENDARI' => 'EXTALLESTIMENTO.CALENDARI',
    'Allest.Manuale0,2' => 'EXTAllest.Manuale0,2',
    'UVSPOTEST' => 'EXTUVSPOTEST',
    'BROSSFILOREFE/A4EST' => 'EXTBROSSFILOREFE/A4EST',
    'BROSSFILOREFE/A5EST' => 'EXTBROSSFILOREFE/A5EST',
    'BROSSCOPEST' => 'EXTBROSSCOPEST',
    'BROSSCOPBANDELLAEST' => 'EXTBROSSCOPBANDELLAEST',
    'BROSSFRESATA/A4EST' => 'EXTBROSSFRESATA/A4EST',
    'PUNTOMETALLICOEST' => 'EXTPUNTOMETALLICOEST',
    'STAMPABUSTE.EST' => 'EXTSTAMPABUSTE.EST',
    'CARTONATO.GEN' => 'EXTCARTONATO.GEN',
];

$totFix = 0;

foreach ($rinominate as $nomeMes => $nomeOnda) {
    // Trova fasi nel MES con il nome rinominato
    $fasi = OrdineFase::where('fase', $nomeMes)->where('stato', '<', 4)->get();

    foreach ($fasi as $fase) {
        $commessa = $fase->ordine->commessa ?? null;
        if (!$commessa) continue;

        // Verifica che in Onda esista con il nome EXT
        $ondaCheck = DB::connection('onda')->select("
            SELECT TOP 1 f.CodFase
            FROM PRDDocFasi f
            JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
            WHERE p.CodCommessa = ? AND f.CodFase = ?
        ", [$commessa, $nomeOnda]);

        if (empty($ondaCheck)) continue; // Non trovato in Onda con EXT, lascia com'è

        // Aggiorna al nome Onda
        $reparto = Reparto::firstOrCreate(['nome' => 'esterno']);
        $catalogo = FasiCatalogo::firstOrCreate(
            ['nome' => $nomeOnda],
            ['reparto_id' => $reparto->id]
        );

        $fase->fase = $nomeOnda;
        $fase->fase_catalogo_id = $catalogo->id;
        $fase->save();

        echo "FIX: {$commessa} | {$nomeMes} → {$nomeOnda}" . PHP_EOL;
        $totFix++;
    }
}

echo PHP_EOL . "Totale rinominate: {$totFix}" . PHP_EOL;
echo "DONE" . PHP_EOL;
