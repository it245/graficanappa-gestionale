<?php
// Confronta TUTTE le fasi tra Onda e MES, mostra solo discrepanze
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\OndaSyncService;

echo "=== CONFRONTO TOTALE ONDA vs MES ===\n";
echo "Data: " . date('d/m/Y H:i') . "\n\n";

$mappaReparti = OndaSyncService::getMappaReparti();

// 1. Prendi tutte le fasi da Onda (commesse aperte)
echo "Caricamento fasi da Onda...\n";
$righeOnda = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        f.CodFase,
        f.CodMacchina
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.TipoDocumento = '2'
      AND t.DataRegistrazione >= CAST('20260227' AS datetime)
      AND f.CodFase IS NOT NULL
    ORDER BY t.CodCommessa, f.CodFase
");

// Raggruppa per commessa e rimappa nomi fase (stessa logica di OndaSyncService)
$fasiOnda = [];
foreach ($righeOnda as $r) {
    $commessa = trim($r->CodCommessa);
    $faseNome = trim($r->CodFase);
    $macchina = trim($r->CodMacchina ?? '');

    // Rimappa STAMPA come fa il sync
    if ($faseNome === 'STAMPA') {
        if (stripos($macchina, 'NO STAMPA') !== false || $macchina === 'NO STAMPA') {
            continue;
        }
        if (stripos($macchina, 'INDIGO') !== false) {
            $faseNome = (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false)
                ? 'STAMPAINDIGOBN' : 'STAMPAINDIGO';
        } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
            $faseNome = 'STAMPAXL106.' . $m[1];
        } else {
            $faseNome = 'STAMPAXL106';
        }
    }

    $fasiOnda[$commessa][$faseNome] = ($fasiOnda[$commessa][$faseNome] ?? 0) + 1;
}

// Aggiungi BRT1 virtuale (il sync la crea sempre)
foreach ($fasiOnda as $commessa => &$fasi) {
    if (!isset($fasi['BRT1']) && !isset($fasi['brt1'])) {
        $fasi['BRT1'] = 1; // il sync la aggiunge automaticamente
    }
}
unset($fasi);

echo "Commesse Onda: " . count($fasiOnda) . "\n";

// 2. Prendi tutte le fasi dal MES
echo "Caricamento fasi dal MES...\n";
$fasiMesRaw = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->select('ordini.commessa', 'ordine_fasi.fase', 'ordine_fasi.stato')
    ->whereNull('ordine_fasi.deleted_at')
    ->orderBy('ordini.commessa')
    ->get();

$fasiMes = [];
foreach ($fasiMesRaw as $r) {
    $commessa = trim($r->commessa);
    $fase = trim($r->fase);
    $fasiMes[$commessa][$fase] = ($fasiMes[$commessa][$fase] ?? 0) + 1;
}

echo "Commesse MES: " . count($fasiMes) . "\n\n";

// 3. Confronta
$commesseTutte = array_unique(array_merge(array_keys($fasiOnda), array_keys($fasiMes)));
sort($commesseTutte);

$problemi = 0;
$dettagli = [];

foreach ($commesseTutte as $commessa) {
    $onda = $fasiOnda[$commessa] ?? [];
    $mes = $fasiMes[$commessa] ?? [];

    // Dedup: nel MES le fasi offset sono raggruppate per commessa (max 1-2)
    // Raggruppa STAMPAXL106* per confronto
    $ondaStampaCount = 0;
    $mesStampaCount = 0;
    $ondaNorm = [];
    $mesNorm = [];

    foreach ($onda as $fase => $count) {
        if (str_starts_with($fase, 'STAMPAXL106')) {
            $ondaStampaCount += $count;
        } else {
            $ondaNorm[$fase] = $count;
        }
    }
    if ($ondaStampaCount > 0) $ondaNorm['STAMPAXL106*'] = min($ondaStampaCount, 2); // dedup max 2

    foreach ($mes as $fase => $count) {
        if (str_starts_with($fase, 'STAMPAXL106')) {
            $mesStampaCount += $count;
        } else {
            $mesNorm[$fase] = $count;
        }
    }
    if ($mesStampaCount > 0) $mesNorm['STAMPAXL106*'] = $mesStampaCount;

    // Dedup fustella per commessa (1 per tipo nel MES)
    // Non serve normalizzare ulteriormente

    $soloOnda = array_diff_key($ondaNorm, $mesNorm);
    $soloMes = array_diff_key($mesNorm, $ondaNorm);

    // Ignora fasi manuali e fasi note come auto-generate
    // Filtra fasi che sono solo rimappature note
    $soloOndaFiltrato = [];
    foreach ($soloOnda as $fase => $count) {
        // Ignora se il nome originale potrebbe essere stato rimappato
        $soloOndaFiltrato[$fase] = $count;
    }

    if (!empty($soloOndaFiltrato) || !empty($soloMes)) {
        $problemi++;
        $riga = "  $commessa:";
        if (!empty($soloOndaFiltrato)) {
            $riga .= " MANCA nel MES: " . implode(', ', array_keys($soloOndaFiltrato));
        }
        if (!empty($soloMes)) {
            $riga .= " EXTRA nel MES: " . implode(', ', array_keys($soloMes));
        }
        $dettagli[] = $riga;
    }
}

// 4. Output
if (empty($dettagli)) {
    echo "Tutte le commesse corrispondono!\n";
} else {
    echo "=== DISCREPANZE TROVATE: $problemi commesse ===\n\n";
    foreach ($dettagli as $d) {
        echo "$d\n";
    }
}

// Commesse solo in Onda (non nel MES)
$soloInOnda = array_diff_key($fasiOnda, $fasiMes);
if (!empty($soloInOnda)) {
    echo "\n=== COMMESSE SOLO IN ONDA (non nel MES): " . count($soloInOnda) . " ===\n";
    foreach (array_keys($soloInOnda) as $c) {
        echo "  $c\n";
    }
}

// Commesse solo nel MES (non in Onda)
$soloInMes = array_diff_key($fasiMes, $fasiOnda);
if (!empty($soloInMes)) {
    echo "\n=== COMMESSE SOLO NEL MES (non in Onda): " . count($soloInMes) . " ===\n";
    foreach (array_keys($soloInMes) as $c) {
        echo "  $c\n";
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Commesse totali: " . count($commesseTutte) . "\n";
echo "Con discrepanze fasi: $problemi\n";
echo "Solo in Onda: " . count($soloInOnda ?? []) . "\n";
echo "Solo nel MES: " . count($soloInMes ?? []) . "\n";
echo "Allineate: " . (count($commesseTutte) - $problemi - count($soloInOnda ?? []) - count($soloInMes ?? [])) . "\n";
