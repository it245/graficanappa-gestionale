<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066998-26';
echo "=== FORMATO REALE COMMESSA {$commessa} ===\n\n";

// 1. Colonne di OC_ATTDocRigheExt
echo "--- 1. Colonne OC_ATTDocRigheExt ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'OC_ATTDocRigheExt'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
}

// 2. Dati OC_ATTDocRigheExt per la commessa (con chiave corretta)
echo "\n--- 2. OC_ATTDocRigheExt dati ---\n";
$ext = DB::connection('onda')->select("
    SELECT TOP 5 e.*
    FROM OC_ATTDocRigheExt e
    WHERE e.OC_CodArtSupporto IS NOT NULL AND e.OC_CodArtSupporto != ''
");
if (!empty($ext)) {
    $first = (array) $ext[0];
    echo "  Colonne chiave: " . implode(', ', array_keys($first)) . "\n\n";
    foreach ($ext as $r) {
        $arr = (array) $r;
        echo "  SuppBase=" . ($arr['OC_SuppBaseCM'] ?? '-');
        echo " SuppAlt=" . ($arr['OC_SuppAltezzaCM'] ?? '-');
        echo " CodSupp=" . ($arr['OC_CodArtSupporto'] ?? '-');
        echo " ArtLavBase=" . ($arr['OC_BaseCMCodArtLav'] ?? '-');
        echo " ArtLavAlt=" . ($arr['OC_AltezzaCMCodArtLav'] ?? '-');
        echo "\n";
    }
}

// 3. MAGAnagraficaArticoli per cod_carta 00W.TR.ELR.70.0003
echo "\n--- 3. MAGAnagraficaArticoli per 00W.TR.ELR.70.0003 ---\n";
$mag = DB::connection('onda')->select("
    SELECT CodArt, Descrizione, OC_AltezzaCM, OC_LarghezzaCM, CodFamiglia,
           OC_FormatoChiuso, CodUnMis, OC_CodUnMisSupporto, OC_CodFamigliaVolta,
           OC_LarghezzaAperta, OC_LunghezzaAperta, OC_NrDimensioni
    FROM MAGAnagraficaArticoli
    WHERE CodArt = '00W.TR.ELR.70.0003'
");
foreach ($mag as $r) {
    $arr = (array) $r;
    foreach ($arr as $k => $v) {
        if ($v !== null && $v !== '' && $v != 0) {
            echo "  {$k} = {$v}\n";
        }
    }
}

// 4. MAGAnagraficaArticoli per l'articolo lavorazione ETICHETTE.INMOULD
echo "\n--- 4. MAGAnagraficaArticoli per ETICHETTE.INMOULD ---\n";
$mag2 = DB::connection('onda')->select("
    SELECT CodArt, Descrizione, OC_AltezzaCM, OC_LarghezzaCM, CodFamiglia,
           OC_FormatoChiuso, OC_CodArtSupportoStd
    FROM MAGAnagraficaArticoli
    WHERE CodArt = 'ETICHETTE.INMOULD'
");
foreach ($mag2 as $r) {
    $arr = (array) $r;
    foreach ($arr as $k => $v) {
        if ($v !== null && $v !== '' && $v != 0) {
            echo "  {$k} = {$v}\n";
        }
    }
}

// 5. MAGFamiglie per il cod_carta
echo "\n--- 5. Famiglia del cod_carta ---\n";
if (!empty($mag)) {
    $famiglia = $mag[0]->CodFamiglia ?? null;
    if ($famiglia) {
        $fam = DB::connection('onda')->select("
            SELECT * FROM MAGFamiglie WHERE CodFamiglia = ?
        ", [$famiglia]);
        foreach ($fam as $r) {
            $arr = (array) $r;
            foreach ($arr as $k => $v) {
                if ($v !== null && $v !== '' && $v != 0) {
                    echo "  {$k} = {$v}\n";
                }
            }
        }
    }
}

// 6. Altra commessa esempio (non IML) per confronto
echo "\n--- 6. PRDDocTeste commessa recente (non IML) ---\n";
$altre = DB::connection('onda')->select("
    SELECT TOP 5 p.CodCommessa, p.CodArt, p.OC_Base, p.OC_Altezza, p.OC_Descrizione
    FROM PRDDocTeste p
    WHERE p.OC_Base > 0 AND p.OC_Altezza > 0
      AND p.CodCommessa LIKE '006699%-26'
    ORDER BY p.CodCommessa DESC
");
foreach ($altre as $r) {
    echo "  {$r->CodCommessa} CodArt={$r->CodArt} Base={$r->OC_Base} Alt={$r->OC_Altezza}\n";
}

echo "\nDone.\n";
