<?php
// Crea fasi PI mancanti per commesse rotte (cod_art generico "Astucci")
// Una fase PI per ogni PRD Onda con descrizione specifica.
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Models\FasiCatalogo;

$dryRun = in_array('--dry-run', $argv);

echo "\n=== Fix PI mancanti (DRY-RUN=" . ($dryRun ? 'SI' : 'NO') . ") ===\n\n";

// Trova commesse con PI mancanti (stesso script check)
$commesseMes = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.fase', ['PI01','PI02','PI03'])
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
        ->whereIn('ordine_fasi.fase', ['PI01','PI02','PI03'])
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
           AND f.CodFase IN ('PI01','PI02','PI03')
         ORDER BY p.IdDoc",
        [$comm]
    );

    $ondaCount = count($ondaPrd);
    if ($ondaCount <= $mesCount) continue;

    $manc = $ondaCount - $mesCount;
    echo "$comm: MES=$mesCount Onda=$ondaCount → creare $manc fasi\n";

    // Trova fase esistente come template
    $fasePivot = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $comm)
        ->whereIn('ordine_fasi.fase', ['PI01','PI02','PI03'])
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordine_fasi.*')
        ->first();

    if (!$fasePivot) { echo "  ⚠ Nessuna fase template trovata, skip\n"; continue; }

    // Crea N-1 nuove fasi (la prima esiste già, ne servono manc altre)
    $descrizioniDaCreare = array_slice($ondaPrd, $mesCount);
    foreach ($descrizioniDaCreare as $prd) {
        $descPrd = $prd->descrizione ?: '[senza descrizione]';
        echo "  + Crea {$prd->CodFase}: " . substr($descPrd, 0, 70) . "\n";

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

echo "\nTotale fasi create: $totCreate\n";
if ($dryRun) echo "DRY-RUN: niente scritto. Rilancia senza --dry-run per applicare.\n";
