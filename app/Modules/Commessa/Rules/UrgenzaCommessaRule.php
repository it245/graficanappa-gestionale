<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Rules;

use Carbon\Carbon;

/**
 * Regole di urgenza/criticità rispetto alla data prevista di consegna.
 * Logica pura: nessuna query DB.
 *
 * @example
 *   $rule = new UrgenzaCommessaRule();
 *   $g = $rule->giorniAllaConsegna(Carbon::parse('2026-05-12')); // es. 5
 *   $rule->eCritica($g);  // true/false (default soglia 2 giorni)
 *   $rule->eRitardo($g);  // true se $g < 0
 */
final class UrgenzaCommessaRule
{
    /**
     * Giorni interi (calendario) tra oggi e la data di consegna.
     * Negativo se la consegna è già passata.
     */
    public function giorniAllaConsegna(Carbon $consegna, ?Carbon $oggi = null): int
    {
        $oggi ??= Carbon::today();

        // Confronto a granularità "giorno": evitiamo l'effetto orario.
        return (int) $oggi->copy()->startOfDay()
            ->diffInDays($consegna->copy()->startOfDay(), false);
    }

    /**
     * True se la commessa è critica: poco tempo alla consegna ma non ancora in ritardo.
     *
     * @param int $giorni              risultato di giorniAllaConsegna()
     * @param int $sogliaCritica       giorni residui sotto i quali la commessa è critica (default 2)
     */
    public function eCritica(int $giorni, int $sogliaCritica = 2): bool
    {
        return $giorni >= 0 && $giorni <= $sogliaCritica;
    }

    /**
     * True se la consegna è già stata superata.
     */
    public function eRitardo(int $giorni): bool
    {
        return $giorni < 0;
    }
}
