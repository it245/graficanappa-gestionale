<?php
/**
 * Controlla i dati di una commessa su Onda (fasi, quantità, macchine).
 * Uso: php check_commessa_onda.php 0066634
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessaArg = $argv[1] ?? null;
if (!$commessaArg) {
    echo "Uso: php check_commessa_onda.php <numero_commessa>\n";
    exit(1);
}

echo "=== ONDA: Commessa {$commessaArg} ===\n\n";

$righe = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        p.OC_Descrizione,
        p.QtaDaProdurre,
        f.CodFase,
        f.CodMacchina,
        f.QtaDaLavorare,
        f.CodUnMis AS UMFase,
        carta.CodArt AS CodCarta,
        carta.Qta AS QtaCarta
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    OUTER APPLY (
        SELECT TOP 1 r.CodArt, r.Qta
        FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
        ORDER BY r.Sequenza
    ) carta
    WHERE t.TipoDocumento = '2'
      AND t.CodCommessa LIKE ?
    ORDER BY p.CodArt, f.CodFase
", [$commessaArg . '%']);

if (empty($righe)) {
    echo "Nessun risultato.\n";
    exit(1);
}

echo "Trovate " . count($righe) . " righe\n\n";

// Pre-fetch scarti macchine
$scartiMacchine = collect(DB::connection('onda')->select(
    "SELECT CodMacchina, OC_FogliScartoIniz FROM PRDMacchinari WHERE OC_FogliScartoIniz > 0"
))->pluck('OC_FogliScartoIniz', 'CodMacchina')->toArray();

// Esplora colonne PRDDocFasi per capire dove sono gli scarti
$colonne = DB::connection('onda')->select("
    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'PRDDocFasi'
    ORDER BY ORDINAL_POSITION
");
echo "=== Colonne PRDDocFasi ===\n";
$colNames = [];
foreach ($colonne as $col) {
    $colNames[] = $col->COLUMN_NAME;
}
echo implode(', ', $colNames) . "\n\n";

// Mostra TUTTI i campi della fase per questa commessa
$righeComplete = DB::connection('onda')->select("
    SELECT f.*
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.TipoDocumento = '2'
      AND t.CodCommessa LIKE ?
      AND f.CodFase IS NOT NULL
    ORDER BY f.CodFase
", [$commessaArg . '%']);

if (!empty($righeComplete)) {
    echo "=== Dettaglio completo fasi ===\n";
    foreach ($righeComplete as $rf) {
        $arr = (array) $rf;
        $fase = $arr['CodFase'] ?? '?';
        echo "  --- {$fase} ---\n";
        foreach ($arr as $k => $v) {
            if ($v !== null && $v !== '' && $v !== 0 && $v !== 0.0) {
                echo "    {$k}: {$v}\n";
            }
        }
    }
    echo "\n";
}

$currentArt = null;
foreach ($righe as $r) {
    if ($currentArt !== $r->CodArt) {
        $currentArt = $r->CodArt;
        echo "--- Art: {$r->CodArt} | Desc: " . substr($r->OC_Descrizione, 0, 80) . "\n";
        echo "    QtaDaProdurre: {$r->QtaDaProdurre} | CodCarta: {$r->CodCarta} | QtaCarta: {$r->QtaCarta}\n";
    }
    if ($r->CodFase) {
        $macchina = $r->CodMacchina ? " (macchina: {$r->CodMacchina})" : '';
        $scarti = isset($scartiMacchine[trim($r->CodMacchina ?? '')]) ? " | Scarti macchina: " . $scartiMacchine[trim($r->CodMacchina)] : '';
        echo "    Fase: {$r->CodFase}{$macchina} | QtaDaLavorare: {$r->QtaDaLavorare} {$r->UMFase}{$scarti}\n";
    }
}

// Documenti Onda (DDT fornitore, OdL, fatture, ecc.)
echo "\n=== ONDA: Documenti (OdL, DDT, fatture) ===\n";
$docs = DB::connection('onda')->select("
    SELECT t.TipoDocumento, t.NumeroDocumento, t.DataRegistrazione, t.DataDocumento,
           a.RagioneSociale, t.StatoDocumento
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.CodCommessa LIKE ?
    ORDER BY t.DataRegistrazione
", [$commessaArg . '%']);

foreach ($docs as $d) {
    echo "  Tipo:{$d->TipoDocumento} | Num:{$d->NumeroDocumento} | DataReg:{$d->DataRegistrazione} | DataDoc:{$d->DataDocumento} | {$d->RagioneSociale} | Stato:{$d->StatoDocumento}\n";
}
if (empty($docs)) echo "  Nessun documento trovato\n";

// Controlla anche il MES
echo "\n=== MES: Fasi nel database ===\n";
$commessaCode = strlen($commessaArg) <= 7 ? str_pad($commessaArg, 7, '0', STR_PAD_LEFT) . '-26' : $commessaArg;
echo "Cerco commessa MES: {$commessaCode}\n";

$ordini = \App\Models\Ordine::where('commessa', 'LIKE', $commessaCode . '%')->get();
foreach ($ordini as $ordine) {
    echo "\nOrdine #{$ordine->id}: {$ordine->commessa} | Art: {$ordine->cod_art} | Qta: {$ordine->qta_richiesta} | QtaCarta: {$ordine->qta_carta}\n";
    $fasi = \App\Models\OrdineFase::where('ordine_id', $ordine->id)->with('faseCatalogo.reparto')->get();
    foreach ($fasi as $fase) {
        $rep = optional(optional($fase->faseCatalogo)->reparto)->nome ?? '?';
        $scarti = $fase->scarti_previsti ? " | ScartiPrev: {$fase->scarti_previsti}" : '';
        echo "  Fase: {$fase->fase} | Stato: {$fase->stato} | QtaFase: {$fase->qta_fase} | QtaProd: {$fase->qta_prod} | Reparto: {$rep}{$scarti}\n";
    }
}
