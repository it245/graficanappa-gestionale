<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CONFRONTO ONDA vs MES ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// 1. Tutte le commesse attive da Onda con le loro fasi
$righeOnda = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        f.CodFase,
        f.CodMacchina,
        COUNT(*) as cnt
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.TipoDocumento = '2'
      AND t.DataRegistrazione >= CAST('20260227' AS datetime)
    GROUP BY t.CodCommessa, f.CodFase, f.CodMacchina
");

// Raggruppa per commessa
$ondaPerCommessa = [];
foreach ($righeOnda as $r) {
    $c = $r->CodCommessa;
    if (!isset($ondaPerCommessa[$c])) $ondaPerCommessa[$c] = [];
    if ($r->CodFase) {
        $ondaPerCommessa[$c][] = $r->CodFase;
    }
}

// 2. Tutte le fasi MES per le stesse commesse
$commesseOnda = array_keys($ondaPerCommessa);
$fasiMES = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->whereIn('ordini.commessa', $commesseOnda)
    ->select('ordini.commessa', 'ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.esterno')
    ->get();

$mesPerCommessa = [];
foreach ($fasiMES as $f) {
    $c = $f->commessa;
    if (!isset($mesPerCommessa[$c])) $mesPerCommessa[$c] = [];
    $mesPerCommessa[$c][] = ['fase' => $f->fase, 'stato' => $f->stato, 'esterno' => $f->esterno];
}

// 3. Confronto
$fasiSoloMES = [];
$fasiSoloOnda = [];
$commesseSoloOnda = [];
$commesseSoloMES = [];
$totOk = 0;

foreach ($ondaPerCommessa as $commessa => $fasiOnda) {
    $fasiOndaUniche = array_unique(array_filter($fasiOnda));

    if (!isset($mesPerCommessa[$commessa])) {
        $commesseSoloOnda[] = $commessa;
        continue;
    }

    $fasiMesNomi = array_unique(array_column($mesPerCommessa[$commessa], 'fase'));

    // Fasi in MES ma non in Onda
    foreach ($fasiMesNomi as $fMes) {
        // Escludi BRT (spedizione) — creata dal MES
        if (str_starts_with($fMes, 'BRT')) continue;
        // Escludi fasi EXT create dal MES
        if (str_starts_with($fMes, 'EXT') && !in_array($fMes, $fasiOndaUniche)) {
            $fasiSoloMES[] = ['commessa' => $commessa, 'fase' => $fMes, 'tipo' => 'EXT_NO_ONDA'];
        } elseif (!in_array($fMes, $fasiOndaUniche)) {
            $fasiSoloMES[] = ['commessa' => $commessa, 'fase' => $fMes, 'tipo' => 'EXTRA_MES'];
        }
    }

    // Fasi in Onda ma non in MES
    foreach ($fasiOndaUniche as $fOnda) {
        if (!in_array($fOnda, $fasiMesNomi)) {
            $fasiSoloOnda[] = ['commessa' => $commessa, 'fase' => $fOnda];
        }
    }
}

// Commesse nel MES ma non in Onda (solo attive)
foreach ($mesPerCommessa as $commessa => $fasi) {
    if (!isset($ondaPerCommessa[$commessa])) {
        $haAttive = collect($fasi)->contains(fn($f) => $f['stato'] < 3);
        if ($haAttive) {
            $commesseSoloMES[] = $commessa;
        }
    }
}

// === OUTPUT ===
echo "Commesse Onda: " . count($ondaPerCommessa) . PHP_EOL;
echo "Commesse MES (con match Onda): " . count($mesPerCommessa) . PHP_EOL . PHP_EOL;

echo "========================================" . PHP_EOL;
echo "COMMESSE IN ONDA MA NON NEL MES: " . count($commesseSoloOnda) . PHP_EOL;
echo "========================================" . PHP_EOL;
foreach (array_slice($commesseSoloOnda, 0, 20) as $c) {
    echo "  {$c}" . PHP_EOL;
}
if (count($commesseSoloOnda) > 20) echo "  ... e altre " . (count($commesseSoloOnda) - 20) . PHP_EOL;
echo PHP_EOL;

echo "========================================" . PHP_EOL;
echo "COMMESSE ATTIVE NEL MES MA NON IN ONDA: " . count($commesseSoloMES) . PHP_EOL;
echo "========================================" . PHP_EOL;
foreach ($commesseSoloMES as $c) {
    echo "  {$c}" . PHP_EOL;
}
echo PHP_EOL;

echo "========================================" . PHP_EOL;
echo "FASI NEL MES MA NON IN ONDA: " . count($fasiSoloMES) . PHP_EOL;
echo "========================================" . PHP_EOL;
// Raggruppa per tipo
$perTipo = [];
foreach ($fasiSoloMES as $f) {
    $perTipo[$f['tipo']][] = $f;
}
foreach ($perTipo as $tipo => $items) {
    echo PHP_EOL . "  [{$tipo}] — " . count($items) . " fasi:" . PHP_EOL;
    foreach (array_slice($items, 0, 15) as $f) {
        echo "    {$f['commessa']} | {$f['fase']}" . PHP_EOL;
    }
    if (count($items) > 15) echo "    ... e altre " . (count($items) - 15) . PHP_EOL;
}
echo PHP_EOL;

echo "========================================" . PHP_EOL;
echo "FASI IN ONDA MA NON NEL MES: " . count($fasiSoloOnda) . PHP_EOL;
echo "========================================" . PHP_EOL;
// Raggruppa per fase
$perFase = [];
foreach ($fasiSoloOnda as $f) {
    $perFase[$f['fase']][] = $f['commessa'];
}
arsort($perFase);
foreach ($perFase as $fase => $commesse) {
    echo "  {$fase}: " . count($commesse) . " commesse" . PHP_EOL;
    if (count($commesse) <= 3) {
        foreach ($commesse as $c) echo "    - {$c}" . PHP_EOL;
    }
}
echo PHP_EOL;

echo "=== RIEPILOGO ===" . PHP_EOL;
echo "Commesse solo Onda: " . count($commesseSoloOnda) . PHP_EOL;
echo "Commesse solo MES (attive): " . count($commesseSoloMES) . PHP_EOL;
echo "Fasi solo MES: " . count($fasiSoloMES) . PHP_EOL;
echo "Fasi solo Onda: " . count($fasiSoloOnda) . PHP_EOL;
