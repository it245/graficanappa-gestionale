<?php

declare(strict_types=1);

namespace App\Modules\Reparti\ValueObjects;

use App\Modules\Reparti\Enums\CodiceReparto;
use InvalidArgumentException;

/**
 * Value Object immutabile: capacità giornaliera di un reparto.
 *
 *  - reparto:         a quale reparto si riferisce
 *  - macchineAttive:  numero di macchine/postazioni operative oggi
 *  - oreMacchina:     ore/giorno per singola macchina (XL106=24, JOH=24, altri=16)
 *
 * La capacità totale è macchine × ore. Il VO è puro: non legge DB,
 * non sa di assenze. Per il calcolo "vivo" che tiene conto di turni
 * effettivi e festivi vedi {@see App\Modules\Reparti\Services\CapacitaService}.
 */
final class CapacitaReparto
{
    public function __construct(
        public readonly CodiceReparto $reparto,
        public readonly int $macchineAttive,
        public readonly int $oreMacchina,
    ) {
        if ($macchineAttive < 0) {
            throw new InvalidArgumentException('macchineAttive non può essere negativo.');
        }
        if ($oreMacchina < 0 || $oreMacchina > 24) {
            throw new InvalidArgumentException("oreMacchina fuori range: {$oreMacchina}.");
        }
    }

    /**
     * Capacità giornaliera in ore = macchine × ore/macchina.
     */
    public function oreGiornaliereTotali(): int
    {
        return $this->macchineAttive * $this->oreMacchina;
    }

    /**
     * Restituisce il default "di targa" per il reparto:
     *  - STAMPA_OFFSET → XL106 24h, 1 macchina (3 turni)
     *  - STAMPA_A_CALDO → JOH 16h, 1 macchina (2 turni)
     *  - tutti gli altri → 16h, 1 macchina (2 turni 6-22)
     *  - ESTERNO/PRODUZIONE/MAGAZZINO → 8h, 1 postazione
     */
    public static function defaultPerReparto(CodiceReparto $reparto): self
    {
        return match ($reparto) {
            CodiceReparto::STAMPA_OFFSET  => new self($reparto, 1, 24),
            CodiceReparto::STAMPA_A_CALDO => new self($reparto, 1, 16),
            CodiceReparto::PRODUZIONE,
            CodiceReparto::MAGAZZINO,
            CodiceReparto::ESTERNO        => new self($reparto, 1, 8),
            default                       => new self($reparto, 1, 16),
        };
    }
}
