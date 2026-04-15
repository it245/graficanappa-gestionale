<?php
/**
 * Esplora formato carta reale per commessa 66998
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066998-26';
echo "=== FORMATO CARTA REALE PER COMMESSA {$commessa} ===\n\n";

// 1. PRDDocTeste: OC_Base e OC_Altezza
echo "--- 1. PRDDocTeste (Base/Altezza commessa) ---\n";
$prd = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Base, p.OC_Altezza, p.OC_Descrizione
    FROM PRDDocTeste p
    WHERE p.CodCommessa = ?
", [$commessa]);
foreach ($prd as $r) {
    echo "  CodArt={$r->CodArt} Base={$r->OC_Base} Altezza={$r->OC_Altezza} Desc={$r->OC_Descrizione}\n";
}

// 2. ATTDocRighe: OC_Base e OC_Altezza per riga
echo "\n--- 2. ATTDocRighe (Base/Altezza per riga ordine) ---\n";
$att = DB::connection('onda')->select("
    SELECT r.CodArt, r.Descrizione, r.OC_Base, r.OC_Altezza, r.Qta
    FROM ATTDocRighe r
    INNER JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ?
", [$commessa]);
foreach ($att as $r) {
    echo "  CodArt={$r->CodArt} Base={$r->OC_Base} Alt={$r->OC_Altezza} Qta={$r->Qta} Desc={$r->Descrizione}\n";
}

// 3. OC_ATTDocRigheExt: supporto base/altezza
echo "\n--- 3. OC_ATTDocRigheExt (Supporto Base/Altezza) ---\n";
$ext = DB::connection('onda')->select("
    SELECT e.OC_SuppBaseCM, e.OC_SuppAltezzaCM, e.OC_CodArtSupporto,
           e.OC_BaseCMCodArtLav, e.OC_AltezzaCMCodArtLav
    FROM OC_ATTDocRigheExt e
    INNER JOIN ATTDocRighe r ON e.IdDoc = r.IdDoc AND e.IdRiga = r.IdRiga
    INNER JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ?
", [$commessa]);
foreach ($ext as $r) {
    echo "  SuppBase={$r->OC_SuppBaseCM} SuppAlt={$r->OC_SuppAltezzaCM} CodArtSupp={$r->OC_CodArtSupporto} ArtLavBase={$r->OC_BaseCMCodArtLav} ArtLavAlt={$r->OC_AltezzaCMCodArtLav}\n";
}

// 4. MAGAnagraficaArticoli: dimensioni del cod_carta
echo "\n--- 4. MAGAnagraficaArticoli (dimensioni articolo carta) ---\n";
$mag = DB::connection('onda')->select("
    SELECT CodArt, Descrizione, OC_AltezzaCM, OC_LarghezzaCM, CodFamiglia, OC_FormatoChiuso, CodUnMis, OC_CodUnMisSupporto
    FROM MAGAnagraficaArticoli
    WHERE CodArt = '00W.TR.ELR.70.0003'
");
foreach ($mag as $r) {
    echo "  CodArt={$r->CodArt} Alt={$r->OC_AltezzaCM} Larg={$r->OC_LarghezzaCM} Famiglia={$r->CodFamiglia} FormatoChiuso={$r->OC_FormatoChiuso} UM={$r->CodUnMis}\n";
}

// 5. MAGFamiglie: info famiglia
echo "\n--- 5. MAGFamiglie ---\n";
$fam = DB::connection('onda')->select("
    SELECT TOP 10 CodFamiglia, Descrizione, OC_SpessoreCartaFamiglia
    FROM MAGFamiglie
    WHERE CodFamiglia LIKE '%W%' OR Descrizione LIKE '%poli%' OR Descrizione LIKE '%carta%'
    ORDER BY CodFamiglia
");
foreach ($fam as $r) {
    echo "  Cod={$r->CodFamiglia} Desc={$r->Descrizione} Spessore={$r->OC_SpessoreCartaFamiglia}\n";
}

// 6. PRDDocFasi: OC_Base e OC_Altezza per fase
echo "\n--- 6. PRDDocFasi (Base/Altezza per fase) ---\n";
$fasi = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodMacchina, f.OC_Base, f.OC_Altezza, f.QtaDaLavorare
    FROM PRDDocFasi f
    INNER JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
    WHERE p.CodCommessa = ?
", [$commessa]);
foreach ($fasi as $r) {
    echo "  Fase={$r->CodFase} Macchina={$r->CodMacchina} Base={$r->OC_Base} Alt={$r->OC_Altezza} Qta={$r->QtaDaLavorare}\n";
}

echo "\nDone.\n";
