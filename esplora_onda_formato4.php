<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066998-26';
echo "=== SUPPORTO REALE COMMESSA {$commessa} ===\n\n";

// 1. OC_ATTDocRigheExt per questa commessa (JOIN tramite OC_IdDoc)
echo "--- 1. OC_ATTDocRigheExt per commessa ---\n";
$ext = DB::connection('onda')->select("
    SELECT e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_CodArtSupporto,
           e.OC_BaseCMCodArtLav, e.OC_AltezzaCMCodArtLav, e.OC_Resa,
           e.OC_TotSupporti, e.OC_CodFustella, e.OC_ColoriBianca, e.OC_ColoriVolta
    FROM OC_ATTDocRigheExt e
    INNER JOIN ATTDocTeste t ON e.OC_IdDoc = t.IdDoc
    WHERE t.CodCommessa = ?
", [$commessa]);
foreach ($ext as $r) {
    echo "  SuppBase={$r->OC_SuppBaseCM} SuppAlt={$r->OC_SuppAltezzaCM} CodSupp={$r->OC_CodArtSupporto}";
    echo " ArtLavBase={$r->OC_BaseCMCodArtLav} ArtLavAlt={$r->OC_AltezzaCMCodArtLav}";
    echo " Resa={$r->OC_Resa} TotSupp={$r->OC_TotSupporti} Fustella={$r->OC_CodFustella}\n";
}

// 2. MAGAnagraficaArticoli per il supporto
echo "\n--- 2. MAGAnagraficaArticoli per 00W.TR.ELR.70.0003 ---\n";
$mag = DB::connection('onda')->select("
    SELECT CodArt, Descrizione, OC_AltezzaCM, OC_LarghezzaCM, CodFamiglia,
           OC_FormatoChiuso, OC_CodArtSupportoStd, OC_CodFamigliaVolta
    FROM MAGAnagraficaArticoli
    WHERE CodArt = '00W.TR.ELR.70.0003'
");
foreach ($mag as $r) {
    $arr = (array) $r;
    foreach ($arr as $k => $v) {
        if ($v !== null && $v !== '' && $v != 0) echo "  {$k} = {$v}\n";
    }
}

// 3. Commesse recenti con supporto diverso dal cod_carta
echo "\n--- 3. Commesse recenti con supporto (ultime 10) ---\n";
$recenti = DB::connection('onda')->select("
    SELECT TOP 10 t.CodCommessa, e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_CodArtSupporto
    FROM OC_ATTDocRigheExt e
    INNER JOIN ATTDocTeste t ON e.OC_IdDoc = t.IdDoc
    WHERE e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
      AND t.DataRegistrazione >= '20260401'
    ORDER BY t.DataRegistrazione DESC
");
foreach ($recenti as $r) {
    echo "  {$r->CodCommessa} Base={$r->OC_SuppBaseCM} Alt={$r->OC_SuppAltezzaCM} Supp={$r->OC_CodArtSupporto}\n";
}

echo "\nDone.\n";
