<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\OrdineFase;
use App\Models\Ordine;

/**
 * Fix multi-modello: dopo onda:sync, commesse con cod_art generico (es. "Astucci")
 * e PRD multipli in Onda hanno solo 1 ordine MES con 1 set di fasi. Per le fasi
 * "per modello" (PIEGAINCOLLA + FINESTRATURA), bisogna creare 1 ordine clone +
 * fasi per ogni PRD distinto.
 *
 * Eseguito automatico dopo cron onda:sync.
 */
class SyncFixMultiModello extends Command
{
    protected $signature = 'onda:sync-fix-multi-modello {--dry-run}';

    protected $description = 'Crea ordini/fasi PI+FIN distinti per commesse multi-PRD con cod_art aggregato';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $gruppi = [
            'PI'    => ['PI01','PI02','PI03'],
            'FIN'   => ['FIN01','FIN03','FIN04','FINESTRATURA.MANUALE'],
            'SFUST' => ['SFUST','SFUST.IML.FUSTELLATO'],
        ];

        $totOrdini = 0;
        $totFasi = 0;

        foreach ($gruppi as $gruppoNome => $faseList) {
            $faseListPlaceholders = "'" . implode("','", $faseList) . "'";

            $commesse = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->whereIn('ordine_fasi.fase', $faseList)
                ->whereNull('ordine_fasi.deleted_at')
                ->whereIn('ordine_fasi.stato', ['0','1','2'])
                ->select('ordini.commessa')
                ->distinct()
                ->pluck('commessa')
                ->toArray();

            foreach ($commesse as $comm) {
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

                $codArtOrdine = $ondaPrd[0]->CodArt ?? '';
                $descRows = DB::connection('onda')->select(
                    "SELECT r.Descrizione
                     FROM ATTDocRighe r
                     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
                     WHERE t.CodCommessa = ? AND r.TipoRiga = 1 AND r.CodArt = ?
                     ORDER BY r.NrRiga",
                    [$comm, $codArtOrdine]
                );
                $descrizioni = array_column((array)$descRows, 'Descrizione');

                $idDocOrdered = array_values(array_unique(array_map(fn($x) => $x->IdDoc, $ondaPrd)));
                $idDocToDesc = [];
                foreach ($idDocOrdered as $i => $idDoc) {
                    $idDocToDesc[$idDoc] = $descrizioni[$i] ?? null;
                }

                $ordineTemplate = Ordine::where('commessa', $comm)->first();
                $faseTemplate = OrdineFase::join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                    ->where('ordini.commessa', $comm)
                    ->whereIn('ordine_fasi.fase', $faseList)
                    ->whereIn('ordine_fasi.stato', ['0','1','2'])
                    ->whereNull('ordine_fasi.deleted_at')
                    ->select('ordine_fasi.*')
                    ->first();

                if (!$ordineTemplate || !$faseTemplate) continue;

                $daCreare = array_slice($ondaPrd, $mesCount);
                foreach ($daCreare as $prd) {
                    $descPrd = $idDocToDesc[$prd->IdDoc] ?? null;
                    if (!$descPrd) continue;

                    if (!$dry) {
                        $nuovoOrdine = $ordineTemplate->replicate();
                        $nuovoOrdine->descrizione = $descPrd;
                        $nuovoOrdine->save();
                        $totOrdini++;

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
                        $totFasi++;
                    } else {
                        $totFasi++;
                    }
                }
            }
        }

        $this->info("Multi-modello: $totOrdini ordini creati, $totFasi fasi create" . ($dry ? ' (DRY-RUN)' : ''));
        return 0;
    }
}
