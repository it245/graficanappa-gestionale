<?php
// V2: rollback fasi precedenti (descrizione_fase NOT NULL) + crea ordini clone con
// descrizione PRD distinta + crea le fasi PI/FIN su nuovi ordini.
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrdineFase;
use App\Models\Ordine;

$dryRun = in_array('--dry-run', $argv);

echo "\n=== Fix v2: ordini distinti per PRD (DRY-RUN=" . ($dryRun ? 'SI' : 'NO') . ") ===\n\n";

// STEP 1: Rollback fasi create da v1 (descrizione_fase popolato)
$daCancellare = OrdineFase::whereNotNull('descrizione_fase')->whereNull('deleted_at')->count();
echo "STEP 1: Rollback fasi v1 con descrizione_fase: $daCancellare\n";
if (!$dryRun && $daCancellare > 0) {
    OrdineFase::whereNotNull('descrizione_fase')->forceDelete();
    echo "  Cancellate.\n";
}

$gruppi = [
    'PI'  => ['PI01','PI02','PI03'],
    'FIN' => ['FIN01','FIN03','FIN04','FINESTRATURA.MANUALE'],
];

$totOrdiniCreati = 0;
$totFasiCreate = 0;

foreach ($gruppi as $gruppoNome => $faseList) {
    echo "\n=== GRUPPO $gruppoNome ===\n";
    $faseListPlaceholders = "'" . implode("','", $faseList) . "'";

    $commesseMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->whereIn('ordine_fasi.fase', $faseList)
        ->whereNull('ordine_fasi.deleted_at')
        ->whereIn('ordine_fasi.stato', ['0','1','2'])
        ->select('ordini.commessa')
        ->distinct()
        ->pluck('commessa')
        ->toArray();

    foreach ($commesseMes as $comm) {
        $mesCount = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $comm)
            ->whereIn('ordine_fasi.fase', $faseList)
            ->whereNull('ordine_fasi.deleted_at')
            ->count();

        $ondaPrd = DB::connection('onda')->select(
            "SELECT p.IdDoc, p.CodArt, f.CodFase, f.QtaDaLavorare
             FROM PRDDocTeste p
             INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
             WHERE p.CodCommessa = ?
               AND f.CodFase IN ($faseListPlaceholders)
             ORDER BY p.IdDoc",
            [$comm]
        );

        if (count($ondaPrd) <= $mesCount) continue;

        // Pre-fetch descrizioni distinte Tipo=1 per CodArt ordine MES
        $codArtOrdine = $ondaPrd[0]->CodArt ?? '';
        $descRows = DB::connection('onda')->select(
            "SELECT r.Descrizione
             FROM ATTDocRighe r
             INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
             WHERE t.CodCommessa = ? AND r.TipoRiga = 1 AND r.CodArt = ?
             ORDER BY r.NrRiga",
            [$comm, $codArtOrdine]
        );
        $descrizioniOrdinate = array_column((array)$descRows, 'Descrizione');

        $idDocOrdered = array_values(array_unique(array_map(fn($x) => $x->IdDoc, $ondaPrd)));
        $idDocToDesc = [];
        foreach ($idDocOrdered as $i => $idDoc) {
            $idDocToDesc[$idDoc] = $descrizioniOrdinate[$i] ?? null;
        }

        $ordineTemplate = Ordine::where('commessa', $comm)->first();
        $faseTemplate = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $comm)
            ->whereIn('ordine_fasi.fase', $faseList)
            ->whereIn('ordine_fasi.stato', ['0','1','2'])
            ->whereNull('ordine_fasi.deleted_at')
            ->select('ordine_fasi.*')
            ->first();

        if (!$ordineTemplate || !$faseTemplate) { echo "$comm: skip (no template)\n"; continue; }

        $daCreare = array_slice($ondaPrd, $mesCount);
        $manc = count($daCreare);
        echo "$comm: creare $manc nuovi (ordini + fasi)\n";

        foreach ($daCreare as $prd) {
            $descPrd = $idDocToDesc[$prd->IdDoc] ?? null;
            if (!$descPrd) { echo "  [SKIP no desc]\n"; continue; }

            echo "  + " . substr($descPrd, 0, 70) . " ({$prd->CodFase})\n";

            if (!$dryRun) {
                // Clone ordine con descrizione PRD
                $nuovoOrdine = $ordineTemplate->replicate();
                $nuovoOrdine->descrizione = $descPrd;
                $nuovoOrdine->save();
                $totOrdiniCreati++;

                // Crea fase sul nuovo ordine
                $nuovaFase = new OrdineFase();
                $nuovaFase->ordine_id        = $nuovoOrdine->id;
                $nuovaFase->fase             = $prd->CodFase;
                $nuovaFase->fase_catalogo_id = $faseTemplate->fase_catalogo_id;
                $nuovaFase->qta_fase         = $prd->QtaDaLavorare ?? $faseTemplate->qta_fase;
                $nuovaFase->stato            = 0;
                $nuovaFase->priorita         = $faseTemplate->priorita;
                $nuovaFase->sequenza         = $faseTemplate->sequenza;
                $nuovaFase->esterno          = $faseTemplate->esterno;
                $nuovaFase->save();
                $totFasiCreate++;
            }
        }
    }
}

echo "\n========================================\n";
echo "Ordini creati: $totOrdiniCreati\n";
echo "Fasi create:   $totFasiCreate\n";
if ($dryRun) echo "DRY-RUN: niente scritto.\n";
