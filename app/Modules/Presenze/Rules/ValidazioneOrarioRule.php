<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Rules;

use Carbon\CarbonInterface;

/**
 * Validazione orari "ammissibili" per timbratura.
 *
 * Regole Grafica Nappa (memoria Mossa37):
 *  - Macchine standard: 6:00 → 22:00, lun-ven
 *  - XL106: 24h, lun-ven
 *  - Sabato/domenica: solo straordinari autorizzati
 *
 * Una timbratura fuori range non è "errore bloccante" — il MES la
 * registra comunque (potrebbe essere uno straordinario approvato),
 * ma viene marcata `fuori_orario_standard = true` e i Service possono
 * decidere se notificare il responsabile (event StraordinariSuperati).
 */
final class ValidazioneOrarioRule
{
    public const ORA_INIZIO_STD = 6;
    public const ORA_FINE_STD = 22;

    public function __construct(
        private readonly bool $turno24h = false,
    ) {}

    public function permessa(CarbonInterface $istante): bool
    {
        // Sabato/domenica = sempre fuori standard (richiede flag esplicito)
        if ($istante->isWeekend()) {
            return false;
        }
        if ($this->turno24h) {
            return true;
        }
        $h = $istante->hour;
        return $h >= self::ORA_INIZIO_STD && $h < self::ORA_FINE_STD;
    }

    public function motivoSeFuori(CarbonInterface $istante): ?string
    {
        if ($istante->isWeekend()) {
            return 'weekend';
        }
        if (!$this->turno24h && ($istante->hour < self::ORA_INIZIO_STD || $istante->hour >= self::ORA_FINE_STD)) {
            return 'orario_notturno';
        }
        return null;
    }
}
