<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\OrdineFase;
use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Services\RepartoService;
use App\Modules\Reportistica\ValueObjects\RigaReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcolo ore lavorate vs ore previste, raggruppate per commessa.
 *
 * Logica estratta da {@see \App\Http\Controllers\DashboardOwnerController::reportOre}.
 *
 * Regola "fonte ore": Prinect ha SEMPRE priorità sul pivot operatore — è
 * decisione del capo (memoria sessione 9/03/2026: indicatore "P" nel report).
 * Il fallback al pivot scatta solo se Prinect non riporta secondi.
 *
 * Esclusioni hardcoded (compat output legacy):
 *  - Fasi BRT1/brt1/BRT (logistica, non produzione)
 *  - Reparto SPEDIZIONE
 *  - Stato <= 2 (non terminate)
 */
final class ReportOreService
{
    public function __construct(
        private readonly RepartoService $reparti,
    ) {}

    /**
     * @return Collection<int|string, RigaReport>  chiavata per commessa
     */
    public function perCommessa(
        Carbon $from,
        Carbon $to,
        ?string $filtroCommessa = null,
        ?int $filtroReparto = null,
    ): Collection {
        $repartoSpedizione = $this->reparti->byCodice(CodiceReparto::SPEDIZIONE);
        $fasiOre = config('fasi_ore', []);

        $query = OrdineFase::with(['ordine.fasi.faseCatalogo', 'faseCatalogo.reparto', 'operatori'])
            ->whereHas('ordine')
            ->whereNotIn('fase', ['BRT1', 'brt1', 'BRT']);

        if ($repartoSpedizione) {
            $query->where(function ($q) use ($repartoSpedizione) {
                $q->whereDoesntHave('faseCatalogo')
                  ->orWhereHas('faseCatalogo', fn ($q2) => $q2->where('reparto_id', '!=', $repartoSpedizione->id));
            });
        }

        if ($filtroCommessa) {
            $query->whereHas('ordine', fn ($q) => $q->where('commessa', 'like', "%{$filtroCommessa}%"));
        }
        if ($filtroReparto) {
            $query->whereHas('faseCatalogo', fn ($q) => $q->where('reparto_id', $filtroReparto));
        }

        $query->where('data_fine', '>=', $from->copy()->startOfDay());
        $query->where('data_fine', '<=', $to->copy()->endOfDay());
        $query->where('stato', '>', 2);

        $fasi = $query->get()->map(function ($fase) use ($fasiOre) {
            $qtaCarta = (float) ($fase->ordine->qta_carta ?? 0);
            $info = $fasiOre[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
            $copieh = $info['copieh'] ?: 1000;
            $fase->ore_previste = round((float) $info['avviamento'] + ($qtaCarta / $copieh), 2);

            // Priorità Prinect → fallback pivot operatori (memoria 9/03/2026).
            $secPrinect = (int) (($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0));
            if ($secPrinect > 0) {
                $fase->ore_lavorate = round($secPrinect / 3600.0, 2);
                $fase->fonte_ore = 'Prinect';
            } else {
                $sec = 0;
                foreach ($fase->operatori as $op) {
                    $inizio = $op->pivot->data_inizio;
                    $fine   = $op->pivot->data_fine;
                    $pausa  = (int) ($op->pivot->secondi_pausa ?? 0);
                    if ($inizio && $fine) {
                        $sec += max(0, Carbon::parse($inizio)->diffInSeconds(Carbon::parse($fine)) - $pausa);
                    }
                }
                $fase->ore_lavorate = round($sec / 3600.0, 2);
                $fase->fonte_ore = $sec > 0 ? 'MES' : '';
            }

            return $fase;
        });

        // Raggruppa per commessa → 1 RigaReport per commessa.
        return $fasi->groupBy(fn ($f) => $f->ordine->commessa ?? '-')->map(function ($gruppo, $commessa) {
            $first   = $gruppo->first();
            $ordine  = $first->ordine;
            $fontePrio = $gruppo->contains(fn ($f) => $f->fonte_ore === 'Prinect') ? 'Prinect'
                : ($gruppo->contains(fn ($f) => $f->fonte_ore === 'MES') ? 'MES' : '');

            return new RigaReport(
                commessa:      (string) $commessa,
                cliente:       $ordine->cliente_nome ?? '-',
                descrizione:   $ordine->descrizione ?? '-',
                dataConsegna:  $ordine->data_prevista_consegna ?? null,
                orePreviste:   round((float) $gruppo->sum('ore_previste'), 2),
                oreEffettive:  round((float) $gruppo->sum('ore_lavorate'), 2),
                numFasi:       $gruppo->count(),
                numTerminate:  $gruppo->where('stato', '>=', 3)->count(),
                fonteOre:      $fontePrio,
                qta:           $ordine->qta_richiesta ?? null,
            );
        });
    }

    /**
     * Conserva il payload "fasi raw" per il template legacy che ancora si
     * aspetta `$commesse->fasi` con la collection di fasi originali.
     *
     * Restituisce {commesse, orePerReparto, fasiRaw} esattamente come
     * compactato dal vecchio metodo controller.
     *
     * @return array{commesse: Collection, orePerReparto: Collection, fasi: Collection}
     */
    public function perTemplate(
        Carbon $from,
        Carbon $to,
        ?string $filtroCommessa = null,
        ?int $filtroReparto = null,
    ): array {
        $repartoSpedizione = $this->reparti->byCodice(CodiceReparto::SPEDIZIONE);
        $fasiOre = config('fasi_ore', []);

        $query = OrdineFase::with(['ordine.fasi.faseCatalogo', 'faseCatalogo.reparto', 'operatori'])
            ->whereHas('ordine')
            ->whereNotIn('fase', ['BRT1', 'brt1', 'BRT']);

        if ($repartoSpedizione) {
            $query->where(function ($q) use ($repartoSpedizione) {
                $q->whereDoesntHave('faseCatalogo')
                  ->orWhereHas('faseCatalogo', fn ($q2) => $q2->where('reparto_id', '!=', $repartoSpedizione->id));
            });
        }
        if ($filtroCommessa) {
            $query->whereHas('ordine', fn ($q) => $q->where('commessa', 'like', "%{$filtroCommessa}%"));
        }
        if ($filtroReparto) {
            $query->whereHas('faseCatalogo', fn ($q) => $q->where('reparto_id', $filtroReparto));
        }
        $query->where('data_fine', '>=', $from->copy()->startOfDay());
        $query->where('data_fine', '<=', $to->copy()->endOfDay());
        $query->where('stato', '>', 2);

        $fasi = $query->get()->map(function ($fase) use ($fasiOre) {
            $qtaCarta = (float) ($fase->ordine->qta_carta ?? 0);
            $info = $fasiOre[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
            $copieh = $info['copieh'] ?: 1000;
            $fase->ore_previste = round((float) $info['avviamento'] + ($qtaCarta / $copieh), 2);

            $secPrinect = (int) (($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0));
            if ($secPrinect > 0) {
                $fase->ore_lavorate = round($secPrinect / 3600.0, 2);
                $fase->fonte_ore = 'Prinect';
            } else {
                $sec = 0;
                foreach ($fase->operatori as $op) {
                    $inizio = $op->pivot->data_inizio;
                    $fine   = $op->pivot->data_fine;
                    $pausa  = (int) ($op->pivot->secondi_pausa ?? 0);
                    if ($inizio && $fine) {
                        $sec += max(0, Carbon::parse($inizio)->diffInSeconds(Carbon::parse($fine)) - $pausa);
                    }
                }
                $fase->ore_lavorate = round($sec / 3600.0, 2);
                $fase->fonte_ore = $sec > 0 ? 'MES' : '';
            }
            return $fase;
        });

        $prioritaConfig = config('fasi_priorita', []);
        $commesse = $fasi->groupBy(fn ($f) => $f->ordine->commessa ?? '-')->map(function ($fasiCommessa, $commessa) use ($prioritaConfig) {
            $first  = $fasiCommessa->first();
            $ordine = $first->ordine;
            return (object) [
                'commessa'      => $commessa,
                'cliente'       => $ordine->cliente_nome ?? '-',
                'descrizione'   => $ordine->descrizione ?? '-',
                'data_consegna' => $ordine->data_prevista_consegna,
                'responsabile'  => $ordine->responsabile ?? '-',
                'ore_previste'  => round((float) $fasiCommessa->sum('ore_previste'), 2),
                'ore_lavorate'  => round((float) $fasiCommessa->sum('ore_lavorate'), 2),
                'fasi'          => $fasiCommessa->sortBy(fn ($f) => $prioritaConfig[$f->fase] ?? 500),
                'num_fasi'      => $fasiCommessa->count(),
                'num_terminate' => $fasiCommessa->where('stato', '>=', 3)->count(),
                'num_avviate'   => $fasiCommessa->where('stato', 2)->count(),
            ];
        })->sortBy('commessa');

        $orePerReparto = $fasi->groupBy(fn ($f) => optional($f->faseCatalogo)->reparto->nome ?? 'Altro')
            ->map(fn ($g) => (object) [
                'previste' => round((float) $g->sum('ore_previste'), 1),
                'lavorate' => round((float) $g->sum('ore_lavorate'), 1),
                'fasi'     => $g->count(),
            ])->sortByDesc('lavorate');

        return [
            'commesse'      => $commesse,
            'orePerReparto' => $orePerReparto,
            'fasi'          => $fasi,
        ];
    }
}
