<?php
// Crea fasi PI/FIN mancanti per commesse rotte (cod_art generico "Astucci")
// Una fase per ogni PRD Onda con descrizione specifica.
//
// Uso:
//   php fix_pi_fin_mancanti.php --dry-run        # solo anteprima
//   php fix_pi_fin_mancanti.php                  # applica entrambi PI+FIN
//   php fix_pi_fin_mancanti.php --solo=PI        # solo piegaincolla
//   php fix_pi_fin_mancanti.php --solo=FIN       # solo finestratura
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrdineFase;

$dryRun = in_array('--dry-run', $argv);
$soloArg = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--solo=')) $soloArg = strtoupper(substr($a, 7));
}

$gruppi = [
    'PI'  => ['PI01','PI02','PI03'],
    'FIN' => ['FIN01','FIN03','FIN04','FINESTRATURA.MANUALE'],
];

if ($soloArg && isset($gruppi[$soloArg])) {
    $gruppi = [$soloArg => $gruppi[$soloArg]];
}

echo "\n=== Fix fasi mancanti (DRY-RUN=" . ($dryRun ? 'SI' : 'NO') . ") ===\n";
echo "Gruppi: " . implode(', ', array_keys($gruppi)) . "\n\n";

$totCreateGlobal = 0;

foreach ($gruppi as $gruppoNome => $faseList) {
    echo str_repeat('=', 60) . "\n";
    echo "GRUPPO $gruppoNome (" . implode(',', $faseList) . ")\n";
    echo str_repeat('=', 60) . "\n";

    $faseListPlaceholders = "'" . implode("','", $faseList) . "'";

    $commesseMes = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->whereIn('ordine_fasi.fase', $faseList)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordini.commessa')
        ->distinct()
        ->pluck('commessa')
        ->toArray();

    $totCreate = 0;
    foreach ($commesseMes as $comm) {
        $mesCount = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $comm)
            ->whereIn('ordine_fasi.fase', $faseList)
            ->whereNull('ordine_fasi.deleted_at')
            ->count();

        $ondaPrd = DB::connection('onda')->select(
            "SELECT p.IdDoc, p.CodArt, f.CodFase, f.QtaDaLavorare,
                    (SELECT TOP 1 r.Descrizione FROM ATTDocRighe r
                     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
                     WHERE t.CodCommessa = p.CodCommessa AND r.TipoRiga = 1 AND r.CodArt = p.CodArt
                     ORDER BY r.NrRiga) AS descrizione
             FROM PRDDocTeste p
             INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
             WHERE p.CodCommessa = ?
               AND f.CodFase IN ($faseListPlaceholders)
             ORDER BY p.IdDoc",
            [$comm]
        );

        $ondaCount = count($ondaPrd);
        if ($ondaCount <= $mesCount) continue;

        $manc = $ondaCount - $mesCount;
        echo "$comm: MES=$mesCount Onda=$ondaCount → creare $manc\n";

        $fasePivot = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $comm)
            ->whereIn('ordine_fasi.fase', $faseList)
            ->whereNull('ordine_fasi.deleted_at')
            ->select('ordine_fasi.*')
            ->first();

        if (!$fasePivot) { echo "  ⚠ skip (no template)\n"; continue; }

        $descrizioniDaCreare = array_slice($ondaPrd, $mesCount);
        foreach ($descrizioniDaCreare as $prd) {
            $descPrd = $prd->descrizione ?: '[senza descrizione]';
            echo "  + {$prd->CodFase}: " . substr($descPrd, 0, 65) . "\n";

            if (!$dryRun) {
                $nuova = new OrdineFase();
                $nuova->ordine_id        = $fasePivot->ordine_id;
                $nuova->fase             = $prd->CodFase;
                $nuova->fase_catalogo_id = $fasePivot->fase_catalogo_id;
                $nuova->descrizione_fase = $descPrd;
                $nuova->qta_fase         = $prd->QtaDaLavorare ?? $fasePivot->qta_fase;
                $nuova->stato            = 0;
                $nuova->priorita         = $fasePivot->priorita;
                $nuova->sequenza         = $fasePivot->sequenza;
                $nuova->esterno          = $fasePivot->esterno;
                $nuova->save();
                $totCreate++;
            }
        }
    }
    echo "\n[$gruppoNome] Totale create: $totCreate\n\n";
    $totCreateGlobal += $totCreate;
}

echo str_repeat('=', 60) . "\n";
echo "TOTALE GLOBALE: $totCreateGlobal fasi create\n";
if ($dryRun) echo "DRY-RUN: niente scritto. Rilancia senza --dry-run per applicare.\n";
