<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Modules\Reportistica\ValueObjects\PeriodoReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Produttività individuale operatori (ore + qta + fasi/giorno).
 *
 * ATTENZIONE — DATI SENSIBILI ART. 4 STATUTO LAVORATORI:
 *
 * Questo service espone metriche di tracciamento individuale. L'uso
 * pubblicistico (dashboard direzione, classifiche) richiede:
 *  - accordo sindacale RSU/RSA o autorizzazione ITL
 *  - informativa GDPR art. 13 firmata da ogni dipendente
 *
 * Vedi MEMORY.md → "Procedura sindacale art. 4". Finché non sono firmati,
 * NON pubblicare la classifica nominativa fuori dal cruscotto direzione.
 */
final class ProduttivitaOperatoriService
{
    /**
     * Top N operatori per fasi completate nel periodo.
     *
     * @return Collection<int, object>
     */
    public function topPerFasi(PeriodoReport $periodo, int $limite = 5): Collection
    {
        return DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('operatori', 'operatori.id', '=', 'fase_operatore.operatore_id')
            ->where('ordine_fasi.stato', '>=', 3)->where('ordine_fasi.stato', '!=', 5)
            ->whereBetween('fase_operatore.data_fine', [$periodo->from, $periodo->to])
            ->select(
                'operatori.nome',
                'operatori.cognome',
                DB::raw('COUNT(*) as fasi_completate'),
                DB::raw('SUM(ordine_fasi.qta_prod) as qta_prodotta'),
            )
            ->groupBy('operatori.id', 'operatori.nome', 'operatori.cognome')
            ->orderByDesc('fasi_completate')
            ->limit($limite)
            ->get();
    }

    /**
     * Performance estesa per ogni operatore: ore_lavorate, tempo_medio_sec,
     * fasi_giorno, reparti.
     *
     * @return Collection<int, object>
     */
    public function performanceCompleta(PeriodoReport $periodo): Collection
    {
        return DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('operatori', 'operatori.id', '=', 'fase_operatore.operatore_id')
            ->where('ordine_fasi.stato', '>=', 3)->where('ordine_fasi.stato', '!=', 5)
            ->whereBetween('fase_operatore.data_fine', [$periodo->from, $periodo->to])
            ->whereNotNull('fase_operatore.data_inizio')
            ->whereNotNull('fase_operatore.data_fine')
            ->select(
                'operatori.id', 'operatori.nome', 'operatori.cognome',
                DB::raw('COUNT(*) as fasi_completate'),
                DB::raw('SUM(ordine_fasi.qta_prod) as qta_prodotta'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec_totali')
            )
            ->groupBy('operatori.id', 'operatori.nome', 'operatori.cognome')
            ->orderByDesc('fasi_completate')
            ->get()
            ->map(function ($op) use ($periodo) {
                $op->ore_lavorate    = round((int) $op->sec_totali / 3600.0, 1);
                $op->tempo_medio_sec = $op->fasi_completate > 0 ? (int) round($op->sec_totali / $op->fasi_completate) : 0;
                $op->fasi_giorno     = round($op->fasi_completate / max($periodo->giorni(), 1), 1);

                $reparti = DB::table('operatore_reparto')
                    ->join('reparti', 'reparti.id', '=', 'operatore_reparto.reparto_id')
                    ->where('operatore_reparto.operatore_id', $op->id)
                    ->pluck('reparti.nome');
                $op->reparti = $reparti->implode(', ');
                return $op;
            });
    }
}
