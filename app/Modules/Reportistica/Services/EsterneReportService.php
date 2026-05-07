<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\OrdineFase;
use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Services\RepartoService;
use App\Modules\Reportistica\Cache\ReportCache;
use Illuminate\Support\Collection;

/**
 * Tracking commesse esterne (lavorazioni in conto terzi).
 *
 * Estratto da {@see \App\Http\Controllers\DashboardOwnerController::esterne}.
 *
 * Logica criterio "fase esterna":
 *  - reparto_id == ESTERNO  OPPURE  flag esterno=1 (impostato dall'owner
 *    inviando manualmente la fase a un fornitore)
 *  - stato < 3 (attiva) OPPURE stato == 5 (esterno dedicato)
 *
 * Lo stato "complessivo" della commessa è il peggiore tra le sue fasi
 * (pausa stringa > 5 > 2 > 1 > 0) — convenzione storica template owner.esterne.
 */
final class EsterneReportService
{
    public function __construct(
        private readonly RepartoService $reparti,
    ) {}

    /**
     * @return Collection<int, object>
     */
    public function commesseEsterne(): Collection
    {
        return ReportCache::remember(
            ReportCache::KEY_ESTERNE,
            ReportCache::TTL_PANORAMICA,
            fn () => $this->calcola(),
        );
    }

    private function calcola(): Collection
    {
        $repartoEsterno = $this->reparti->byCodice(CodiceReparto::ESTERNO);

        $fasiEsterne = OrdineFase::where(fn ($q) => $q->where('stato', '<', 3)->orWhere('stato', 5))
            ->where(function ($q) use ($repartoEsterno) {
                if ($repartoEsterno) {
                    $q->whereHas('faseCatalogo', fn ($q2) => $q2->where('reparto_id', $repartoEsterno->id));
                }
                $q->orWhere('esterno', 1);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo'])
            ->get();

        if ($fasiEsterne->isEmpty()) {
            return collect();
        }

        return $fasiEsterne->groupBy('ordine_id')->map(function ($fasi) {
            $prima  = $fasi->first();
            $ordine = $prima->ordine;

            // Fornitore: estratto da nota "Inviato a:" della prima fase che ce l'ha.
            $fornitore = '-';
            foreach ($fasi as $f) {
                if (preg_match('/Inviato a:\s*(.+)/i', $f->note ?? '', $mf)) {
                    $fornitore = trim($mf[1]);
                    break;
                }
            }

            $stati = $fasi->pluck('stato');
            if ($stati->contains(fn ($s) => is_string($s) && !is_numeric($s))) {
                $statoPausa = $fasi->first(fn ($f) => is_string($f->stato) && !is_numeric($f->stato));
                $stato = $statoPausa->stato;
            } elseif ($stati->contains(5)) {
                $stato = 5;
            } elseif ($stati->contains(2)) {
                $stato = 2;
            } elseif ($stati->contains(1)) {
                $stato = 1;
            } else {
                $stato = 0;
            }

            $dataInvio = $fasi->whereNotNull('data_inizio')->min('data_inizio');

            return (object) [
                'ordine'          => $ordine,
                'fornitore'       => $fornitore,
                'stato'           => $stato,
                'fasi'            => $fasi->pluck('faseCatalogo.nome_display', 'id')->filter()->values()->implode(', ')
                                      ?: $fasi->pluck('fase')->implode(', '),
                'fasi_dettaglio'  => $fasi->map(fn ($f) => [
                    'id'   => $f->id,
                    'nome' => $f->faseCatalogo->nome_display ?? $f->fase,
                    'qta'  => $f->qta_fase ?? $f->ordine->qta_richiesta ?? 0,
                ])->values()->toArray(),
                'num_fasi'   => $fasi->count(),
                'fasi_ids'   => $fasi->pluck('id')->values()->toArray(),
                'data_invio' => $dataInvio,
                'note'       => $fasi->pluck('note')->filter()->unique()->implode(' | '),
            ];
        })->sortBy(fn ($c) => $c->ordine->data_prevista_consegna ?? '9999-12-31')->values();
    }
}
