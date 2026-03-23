<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICA FASI EXT: esistono in Onda? ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le fasi EXT nel MES (con esterno flag o nome EXT*)
$fasiExt = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where(function ($q) {
        $q->where('ordine_fasi.esterno', 1)
          ->orWhere('ordine_fasi.fase', 'LIKE', 'EXT%');
    })
    ->where('ordine_fasi.stato', '<', 4) // escludi consegnate
    ->select('ordini.commessa', 'ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.esterno', 'ordine_fasi.id as fase_id')
    ->orderBy('ordini.commessa')
    ->get();

// Raggruppa per commessa
$perCommessa = [];
foreach ($fasiExt as $f) {
    $perCommessa[$f->commessa][] = $f;
}

echo "Commesse con fasi EXT/esterne: " . count($perCommessa) . PHP_EOL;
echo "Totale fasi EXT: " . $fasiExt->count() . PHP_EOL . PHP_EOL;

$inOnda = 0;
$nonInOnda = 0;
$dettaglioNonOnda = [];

foreach ($perCommessa as $commessa => $fasiMes) {
    // Cerca fasi in Onda per questa commessa
    $codCommessa = $commessa;
    $fasiOnda = DB::connection('onda')->select("
        SELECT DISTINCT f.CodFase
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE t.CodCommessa = ?
    ", [$codCommessa]);

    $fasiOndaNomi = array_map(fn($r) => $r->CodFase, $fasiOnda);

    foreach ($fasiMes as $f) {
        if (in_array($f->fase, $fasiOndaNomi)) {
            $inOnda++;
        } else {
            $nonInOnda++;
            $dettaglioNonOnda[] = [
                'commessa' => $commessa,
                'fase_mes' => $f->fase,
                'stato' => $f->stato,
                'esterno' => $f->esterno,
                'fasi_onda' => implode(', ', $fasiOndaNomi),
            ];
        }
    }
}

echo "========================================" . PHP_EOL;
echo "FASI EXT PRESENTI IN ONDA: {$inOnda} (OK)" . PHP_EOL;
echo "FASI EXT NON IN ONDA: {$nonInOnda} (SOSPETTE)" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

// Raggruppa per fase MES
$perFase = [];
foreach ($dettaglioNonOnda as $d) {
    $perFase[$d['fase_mes']][] = $d;
}

foreach ($perFase as $fase => $items) {
    echo "[{$fase}] — " . count($items) . " commesse NON in Onda:" . PHP_EOL;
    foreach (array_slice($items, 0, 5) as $i) {
        echo "  {$i['commessa']} | stato:{$i['stato']} | esterno:" . ($i['esterno'] ? 'SI' : 'NO') . PHP_EOL;
        echo "    Fasi Onda: {$i['fasi_onda']}" . PHP_EOL;
    }
    if (count($items) > 5) echo "  ... e altre " . (count($items) - 5) . PHP_EOL;
    echo PHP_EOL;
}
