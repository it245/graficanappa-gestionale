<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reparto;
use App\Models\CostoReparto;
use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ReportCostiMarginiExport;
use Maatwebsite\Excel\Facades\Excel;

class CostiMarginiController extends Controller
{
    // =====================================================================
    // CONFIGURAZIONE TARIFFE
    // =====================================================================

    public function configTariffe()
    {
        $reparti = Reparto::with('costiOrari')->orderBy('nome')->get();
        return view('admin.costi.config_tariffe', compact('reparti'));
    }

    public function salvaTariffa(Request $request)
    {
        $request->validate([
            'reparto_id'   => 'required|exists:reparti,id',
            'costo_orario' => 'required|numeric|min:0',
            'valido_dal'   => 'required|date',
            'valido_al'    => 'nullable|date|after_or_equal:valido_dal',
            'note'         => 'nullable|string|max:255',
        ]);

        // Se tariffa corrente (valido_al vuoto), chiudi la precedente
        if (is_null($request->valido_al)) {
            CostoReparto::where('reparto_id', $request->reparto_id)
                ->whereNull('valido_al')
                ->update(['valido_al' => Carbon::parse($request->valido_dal)->subDay()->toDateString()]);
        }

        CostoReparto::create($request->only(['reparto_id', 'costo_orario', 'valido_dal', 'valido_al', 'note']));

        return redirect()->route('admin.costi.tariffe')
                         ->with('success', 'Tariffa salvata correttamente.');
    }

    public function eliminaTariffa($id)
    {
        CostoReparto::findOrFail($id)->delete();
        return redirect()->route('admin.costi.tariffe')
                         ->with('success', 'Tariffa eliminata.');
    }

    // =====================================================================
    // REPORT COSTI & MARGINI
    // =====================================================================

    public function reportCosti(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        if (!in_array($periodo, ['settimana', 'mese', 'trimestre', 'semestre', 'anno'])) {
            $periodo = 'mese';
        }

        [$da, $a, $daPrev, $aPrev] = $this->parsePeriodo($periodo);

        $labelPeriodo = $da->format('d M Y') . ' - ' . $a->format('d M Y');
        $labelPrecedente = $daPrev->format('d M Y') . ' - ' . $aPrev->format('d M Y');

        $kpi = $this->calcolaKpiCosti($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiCosti($daPrev, $aPrev, $periodo);

        // Delta %
        $delta = (object)[];
        $delta->totaleValore = $kpiPrev->totaleValore > 0
            ? round((($kpi->totaleValore - $kpiPrev->totaleValore) / $kpiPrev->totaleValore) * 100, 1) : null;
        $delta->costoTotaleLav = $kpiPrev->costoTotaleLav > 0
            ? round((($kpi->costoTotaleLav - $kpiPrev->costoTotaleLav) / $kpiPrev->costoTotaleLav) * 100, 1) : null;
        $delta->costoTotaleMat = $kpiPrev->costoTotaleMat > 0
            ? round((($kpi->costoTotaleMat - $kpiPrev->costoTotaleMat) / $kpiPrev->costoTotaleMat) * 100, 1) : null;
        $delta->margineTotal = $kpiPrev->margineTotal != 0
            ? round((($kpi->margineTotal - $kpiPrev->margineTotal) / abs($kpiPrev->margineTotal)) * 100, 1) : null;
        $delta->marginePercMedio = round($kpi->marginePercMedio - $kpiPrev->marginePercMedio, 1);
        $delta->commesseInPerdita = $kpiPrev->commesseInPerdita > 0
            ? round((($kpi->commesseInPerdita - $kpiPrev->commesseInPerdita) / $kpiPrev->commesseInPerdita) * 100, 1) : null;

        return view('admin.costi.report_costi', compact(
            'periodo', 'labelPeriodo', 'labelPrecedente',
            'kpi', 'kpiPrev', 'delta'
        ));
    }

    public function reportCostiExcel(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        if (!in_array($periodo, ['settimana', 'mese', 'trimestre', 'semestre', 'anno'])) {
            $periodo = 'mese';
        }

        [$da, $a, $daPrev, $aPrev] = $this->parsePeriodo($periodo);
        $kpi = $this->calcolaKpiCosti($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiCosti($daPrev, $aPrev, $periodo);

        $labelPeriodo = $da->format('d-m-Y') . '_' . $a->format('d-m-Y');

        return Excel::download(
            new ReportCostiMarginiExport($kpi, $kpiPrev, $periodo),
            "ReportCostiMargini_{$periodo}_{$labelPeriodo}.xlsx"
        );
    }

    // =====================================================================
    // HELPER PRIVATI
    // =====================================================================

    private function parsePeriodo(string $periodo): array
    {
        $oggi = Carbon::today();
        return match ($periodo) {
            'settimana' => [
                $oggi->copy()->subWeek(), $oggi->copy()->endOfDay(),
                $oggi->copy()->subWeeks(2), $oggi->copy()->subWeek()
            ],
            'trimestre' => [
                $oggi->copy()->subMonths(3), $oggi->copy()->endOfDay(),
                $oggi->copy()->subMonths(6), $oggi->copy()->subMonths(3)
            ],
            'semestre' => [
                $oggi->copy()->subMonths(6), $oggi->copy()->endOfDay(),
                $oggi->copy()->subMonths(12), $oggi->copy()->subMonths(6)
            ],
            'anno' => [
                $oggi->copy()->subYear(), $oggi->copy()->endOfDay(),
                $oggi->copy()->subYears(2), $oggi->copy()->subYear()
            ],
            default => [
                $oggi->copy()->subMonth(), $oggi->copy()->endOfDay(),
                $oggi->copy()->subMonths(2), $oggi->copy()->subMonth()
            ],
        };
    }

    private function calcolaKpiCosti($da, $a, string $periodo = 'mese'): object
    {
        // Preload tariffe per tutti i reparti (evita N query per commessa)
        $tariffe = CostoReparto::all()->groupBy('reparto_id');

        $tariffaPerReparto = function (int $repartoId, string $data) use ($tariffe): float {
            if (!$tariffe->has($repartoId)) return 0.0;
            $tariffa = $tariffe[$repartoId]
                ->filter(fn($t) => $t->valido_dal->lte($data) && ($t->valido_al === null || $t->valido_al->gte($data)))
                ->sortByDesc('valido_dal')
                ->first();
            return $tariffa ? (float) $tariffa->costo_orario : 0.0;
        };

        // 1. Commesse con attivita' completate nel periodo
        $commesseNelPeriodo = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->select('ordini.commessa')
            ->distinct()
            ->pluck('ordini.commessa');

        // 2. Per ogni commessa: calcola costi
        $dettaglioCommesse = collect();
        $totaleCostoLav = 0;
        $totaleCostoMat = 0;
        $totaleValore = 0;
        $commesseInPerdita = 0;

        foreach ($commesseNelPeriodo as $commessa) {
            $ordine = Ordine::where('commessa', $commessa)->first();
            if (!$ordine) continue;

            // Ore effettive per fase → costo per reparto
            $fasiCosti = DB::table('fase_operatore as fo')
                ->join('ordine_fasi as of', 'of.id', '=', 'fo.fase_id')
                ->join('ordini as o', 'o.id', '=', 'of.ordine_id')
                ->leftJoin('fasi_catalogo as fc', 'fc.id', '=', 'of.fase_catalogo_id')
                ->where('o.commessa', $commessa)
                ->where('of.stato', '>=', 3)
                ->whereNotNull('fo.data_inizio')
                ->whereNotNull('fo.data_fine')
                ->select(
                    'fc.reparto_id',
                    DB::raw('SUM(TIMESTAMPDIFF(SECOND, fo.data_inizio, fo.data_fine) - COALESCE(fo.secondi_pausa, 0)) as sec_totali'),
                    DB::raw('MIN(fo.data_inizio) as prima_inizio')
                )
                ->groupBy('fc.reparto_id')
                ->get();

            $costoLav = 0;
            $oreEffettive = 0;
            foreach ($fasiCosti as $f) {
                $ore = $f->sec_totali / 3600;
                $oreEffettive += $ore;
                $dataRif = $f->prima_inizio
                    ? Carbon::parse($f->prima_inizio)->toDateString()
                    : $da->toDateString();
                $tariffa = $f->reparto_id ? $tariffaPerReparto($f->reparto_id, $dataRif) : 0;
                $costoLav += $ore * $tariffa;
            }

            // Ore stimate da ordine_fasi.ore
            $oreStimate = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                ->sum('ore') ?? 0;

            $costoMat = (float) ($ordine->costo_materiali ?? 0);
            $valoreOrdine = (float) ($ordine->valore_ordine ?? 0);
            $costoTotale = $costoLav + $costoMat;
            $margine = $valoreOrdine > 0 ? $valoreOrdine - $costoTotale : null;
            $marginePerc = ($valoreOrdine > 0 && $costoTotale > 0)
                ? round(($margine / $valoreOrdine) * 100, 1)
                : null;
            $deltaOrePct = ($oreStimate > 0 && $oreEffettive > 0)
                ? round((($oreEffettive - $oreStimate) / $oreStimate) * 100, 1)
                : null;

            if ($margine !== null && $margine < 0) $commesseInPerdita++;

            $totaleCostoLav += $costoLav;
            $totaleCostoMat += $costoMat;
            if ($valoreOrdine > 0) $totaleValore += $valoreOrdine;

            $dettaglioCommesse->push((object)[
                'commessa'       => $commessa,
                'cliente'        => $ordine->cliente_nome ?? '-',
                'descrizione'    => $ordine->descrizione ?? '-',
                'valore_ordine'  => $valoreOrdine,
                'costo_lav'      => round($costoLav, 2),
                'costo_materiali' => $costoMat,
                'costo_totale'   => round($costoTotale, 2),
                'margine'        => $margine !== null ? round($margine, 2) : null,
                'margine_pct'    => $marginePerc,
                'ore_stimate'    => round($oreStimate, 1),
                'ore_effettive'  => round($oreEffettive, 1),
                'delta_ore_pct'  => $deltaOrePct,
                'data_consegna'  => $ordine->data_prevista_consegna,
            ]);
        }

        // 3. Aggregati
        $numCommesse = $dettaglioCommesse->count();
        $costoTotale = $totaleCostoLav + $totaleCostoMat;
        $commesseConValore = $dettaglioCommesse->filter(fn($c) => $c->valore_ordine > 0);
        $margineTotal = $commesseConValore->sum('margine');
        $marginePercMedio = $totaleValore > 0
            ? round(($margineTotal / $totaleValore) * 100, 1)
            : 0;
        $costoMedioCommessa = $numCommesse > 0 ? round($costoTotale / $numCommesse, 2) : 0;

        // 4. Top profittevoli / in perdita
        $topProfittevoli = $dettaglioCommesse
            ->filter(fn($c) => $c->margine !== null)
            ->sortByDesc('margine_pct')
            ->take(10)->values();

        $topPerdita = $dettaglioCommesse
            ->filter(fn($c) => $c->margine !== null && $c->margine < 0)
            ->sortBy('margine')
            ->take(10)->values();

        // 5. Costo per reparto (aggregato periodo)
        $costoPerReparto = DB::table('fase_operatore as fo')
            ->join('ordine_fasi as of', 'of.id', '=', 'fo.fase_id')
            ->leftJoin('fasi_catalogo as fc', 'fc.id', '=', 'of.fase_catalogo_id')
            ->join('reparti as r', 'r.id', '=', 'fc.reparto_id')
            ->where('of.stato', '>=', 3)
            ->whereBetween('fo.data_fine', [$da, $a])
            ->whereNotNull('fo.data_inizio')
            ->whereNotNull('fo.data_fine')
            ->select(
                'r.id as reparto_id',
                'r.nome as reparto_nome',
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fo.data_inizio, fo.data_fine) - COALESCE(fo.secondi_pausa, 0)) as sec_totali')
            )
            ->groupBy('r.id', 'r.nome')
            ->get()
            ->map(function ($row) use ($tariffaPerReparto, $da) {
                $ore = $row->sec_totali / 3600;
                $tariffa = $tariffaPerReparto($row->reparto_id, $da->toDateString());
                $row->ore = round($ore, 1);
                $row->tariffa = $tariffa;
                $row->costo = round($ore * $tariffa, 2);
                return $row;
            })
            ->sortByDesc('costo')
            ->values();

        // 6. Trend margine (granularita adattiva)
        if ($periodo === 'anno') {
            $groupExpr = DB::raw("DATE_FORMAT(fo.data_fine, '%Y-%m') as periodo");
            $granularita = 'mese';
        } elseif (in_array($periodo, ['trimestre', 'semestre'])) {
            $groupExpr = DB::raw("CONCAT(YEAR(fo.data_fine), '-W', LPAD(WEEK(fo.data_fine, 1), 2, '0')) as periodo");
            $granularita = 'settimana';
        } else {
            $groupExpr = DB::raw("DATE(fo.data_fine) as periodo");
            $granularita = 'giorno';
        }

        $trendMargine = DB::table('fase_operatore as fo')
            ->join('ordine_fasi as of', 'of.id', '=', 'fo.fase_id')
            ->join('ordini as o', 'o.id', '=', 'of.ordine_id')
            ->where('of.stato', '>=', 3)
            ->whereBetween('fo.data_fine', [$da, $a])
            ->whereNotNull('fo.data_inizio')
            ->whereNotNull('fo.data_fine')
            ->select(
                $groupExpr,
                DB::raw('COUNT(DISTINCT o.commessa) as n_commesse'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fo.data_inizio, fo.data_fine) - COALESCE(fo.secondi_pausa, 0)) as sec_totali')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->map(function ($r) {
                $r->ore = round($r->sec_totali / 3600, 1);
                return $r;
            });

        return (object)[
            'numCommesse'        => $numCommesse,
            'costoTotaleLav'     => round($totaleCostoLav, 2),
            'costoTotaleMat'     => round($totaleCostoMat, 2),
            'costoTotale'        => round($costoTotale, 2),
            'totaleValore'       => round($totaleValore, 2),
            'margineTotal'       => round($margineTotal, 2),
            'marginePercMedio'   => $marginePercMedio,
            'costoMedioCommessa' => $costoMedioCommessa,
            'commesseInPerdita'  => $commesseInPerdita,
            'dettaglioCommesse'  => $dettaglioCommesse->sortByDesc('margine_pct')->values(),
            'topProfittevoli'    => $topProfittevoli,
            'topPerdita'         => $topPerdita,
            'costoPerReparto'    => $costoPerReparto,
            'trendMargine'       => $trendMargine,
            'granularita'        => $granularita,
        ];
    }
}
