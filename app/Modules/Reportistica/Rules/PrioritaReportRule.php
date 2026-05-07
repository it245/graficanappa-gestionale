<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Rules;

use Carbon\Carbon;

/**
 * Regola pura: ranking commesse per "urgenza report" (più alto = più urgente).
 *
 * Score sommativo:
 *  - giorni di ritardo  → +10 ciascuno
 *  - giorni mancanti <=3 → +5
 *  - sforatura ore       → +3
 *  - priorità manuale    → +20
 *
 * NB: non sostituisce {@see \App\Services\PriorityService} dello scheduler
 * Mossa 37 — qui si tratta solo di ordinare le righe in tabella per il
 * report direzionale.
 */
final class PrioritaReportRule
{
    public static function score(?string $dataConsegna, bool $sforata = false, bool $prioritaManuale = false): int
    {
        $score = 0;
        if ($prioritaManuale) {
            $score += 20;
        }
        if ($sforata) {
            $score += 3;
        }
        if ($dataConsegna) {
            try {
                $consegna = Carbon::parse($dataConsegna)->endOfDay();
                $oggi = Carbon::now();
                if ($consegna->lt($oggi)) {
                    $ritardo = (int) $consegna->diffInDays($oggi);
                    $score += $ritardo * 10;
                } elseif ($consegna->diffInDays($oggi) <= 3) {
                    $score += 5;
                }
            } catch (\Throwable) {
                // data malformata → 0
            }
        }
        return $score;
    }
}
