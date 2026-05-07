<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Enums;

/**
 * Stato di un workstep nel Pressroom Manager Heidelberg.
 *
 * Valori esatti come restituiti dall'API REST (campo "status" su workstep).
 * Mantenere il backing string allineato all'API: confronti case-sensitive.
 */
enum StatoWorkstep: string
{
    case Waiting   = 'WAITING';
    case Running   = 'RUNNING';
    case Completed = 'COMPLETED';
    case Aborted   = 'ABORTED';

    /**
     * Costruisce dall'eventuale string raw API. Restituisce null per
     * stati sconosciuti (es. "SETUP", "PAUSED" futuri) invece di sollevare:
     * il chiamante decide la fallback policy.
     */
    public static function tryFromApi(?string $raw): ?self
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return self::tryFrom($raw);
    }

    /**
     * Stato terminale (non più attivabile, non più produrrà fogli).
     */
    public function isTerminale(): bool
    {
        return match ($this) {
            self::Completed, self::Aborted => true,
            self::Waiting, self::Running   => false,
        };
    }
}
