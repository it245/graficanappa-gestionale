<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Services;

use App\Models\OrdineFase;
use App\Modules\Scheduling\Enums\LivelloPriorita;
use App\Modules\Scheduling\Rules\BatchAffinityRule;
use App\Modules\Scheduling\Rules\SequenzaFasiRule;
use App\Modules\Scheduling\Rules\UrgenzaRule;
use Illuminate\Support\Collection;

/**
 * Combina i 4 livelli di priorità in un punteggio scalare ordinabile.
 *
 * Più ALTO = più prioritario. Lo scheduler ordina la coda DESC.
 * I pesi (vedi {@see LivelloPriorita::peso()}) sono tarati in modo
 * che ogni livello superiore domini lessicograficamente quelli
 * inferiori, pur restituendo un singolo float testabile.
 */
final class PrioritaService
{
    public function calcolaPriorita(OrdineFase $fase): float
    {
        $disp  = SequenzaFasiRule::puoIniziare($fase) ? 1.0 : 0.0;

        $u = UrgenzaRule::urgenza($fase);
        // Più piccolo (o negativo = ritardo) → più urgente.
        // Normalizzo in [0..1] con clamp a ±30 gg.
        if ($u === PHP_FLOAT_MAX) {
            $urgScore = 0.0;
        } else {
            $clamp    = max(-30.0, min(30.0, $u));
            $urgScore = (30.0 - $clamp) / 60.0;
        }

        // Affinità batch: 1 se ha un batch group definito.
        $batch     = BatchAffinityRule::calcolaBatchGroup($fase);
        $batchScore = $batch !== '' ? 1.0 : 0.0;

        // Sequenza: invertita (seq bassa = a monte = più prioritaria).
        $seq      = SequenzaFasiRule::ordineFase((string) $fase->fase);
        $seqScore = $seq === PHP_INT_MAX ? 0.0 : (1.0 / (1 + $seq));

        return $disp       * LivelloPriorita::Disponibilita->peso()
             + $urgScore   * LivelloPriorita::Urgenza->peso()
             + $batchScore * LivelloPriorita::BatchAffinity->peso()
             + $seqScore   * LivelloPriorita::SequenzaCiclo->peso();
    }

    /**
     * Ordina una collection di fasi DESC per priorità.
     *
     * @param Collection<int,OrdineFase> $fasi
     * @return Collection<int,OrdineFase>
     */
    public function sortQueue(Collection $fasi): Collection
    {
        return $fasi
            ->sortByDesc(fn (OrdineFase $f): float => $this->calcolaPriorita($f))
            ->values();
    }
}
