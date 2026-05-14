<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ordine;
use App\Models\OrdineFase;

$comm = '0067235-26';

// Prendi 6 IdDoc Onda per questa commessa
$prd = DB::connection('onda')->select(
    "SELECT IdDoc, CodArt FROM PRDDocTeste WHERE CAST(CodCommessa AS VARCHAR) = ? ORDER BY IdDoc",
    [substr($comm, 0, 7) . '-' . substr($comm, -2)]
);
echo "PRD Onda: " . count($prd) . "\n";

// Prendi descrizioni distinte ATTDocRighe per ogni CodArt='Astucci'
$desc = DB::connection('onda')->select(
    "SELECT r.IdDoc, r.NrRiga, r.Descrizione
     FROM ATTDocRighe r
     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
     WHERE CAST(t.CodCommessa AS VARCHAR) = ?
       AND r.TipoRiga = 1
       AND r.CodArt = 'Astucci'
     ORDER BY r.NrRiga",
    [$comm]
);
$descrizioni = array_column((array)$desc, 'Descrizione');
echo "Descrizioni Onda trovate: " . count($descrizioni) . "\n";
foreach ($descrizioni as $i => $d) {
    echo "  [$i] " . substr($d, 0, 80) . "\n";
}

// Prendi tutte fasi PI01 della commessa, ordinate per id
$ordini = Ordine::where('commessa', $comm)->pluck('id')->toArray();
$fasi = OrdineFase::whereIn('ordine_id', $ordini)
    ->where('fase', 'PI01')
    ->orderBy('id')
    ->get(['id','ordine_id','descrizione_fase','qta_prod','stato']);

echo "\nFasi PI01 MES: " . $fasi->count() . "\n";
foreach ($fasi as $i => $f) {
    $newDesc = $descrizioni[$i] ?? null;
    if (!$newDesc) {
        echo "  Fase id={$f->id}: nessuna descrizione disponibile\n";
        continue;
    }
    echo "  Fase id={$f->id} (stato={$f->stato}, qta={$f->qta_prod})\n";
    echo "    PRIMA: " . substr($f->descrizione_fase ?? '-', 0, 80) . "\n";
    echo "    DOPO:  " . substr($newDesc, 0, 80) . "\n";
    $f->descrizione_fase = $newDesc;
    $f->save();
}

echo "\nFatto.\n";
