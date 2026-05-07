<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Enums;

/**
 * Tipologia di fustella/cliché in uso a Grafica Nappa.
 *
 * - PIANA       : fustella piana (BOBST)
 * - ROTATIVA    : fustella rotativa / cilindrica
 * - TRANCIATURA : taglio a fustella per cordonatura/incisione
 * - RILIEVO     : cliché per rilievo a secco / a caldo (JOH)
 */
enum TipoFustella: string
{
    case PIANA = 'PIANA';
    case ROTATIVA = 'ROTATIVA';
    case TRANCIATURA = 'TRANCIATURA';
    case RILIEVO = 'RILIEVO';

    public function label(): string
    {
        return match ($this) {
            self::PIANA => 'Fustella Piana',
            self::ROTATIVA => 'Fustella Rotativa',
            self::TRANCIATURA => 'Tranciatura',
            self::RILIEVO => 'Rilievo / Cliché',
        };
    }

    /**
     * Mapping da codice fase (es. "FUSTBOBST", "RILIEVOASECCOJOH") al tipo fustella.
     *
     * Esempi:
     *   FUSTELLATURA72X51 → PIANA
     *   FUSTBOBST75X106   → PIANA
     *   BOBSTROTATIVA     → ROTATIVA
     *   RILIEVOASECCOJOH  → RILIEVO
     *   TRANCIA*          → TRANCIATURA
     */
    public static function dalCodiceFase(string $cod): self
    {
        $up = strtoupper(trim($cod));

        if (str_contains($up, 'RILIEVO') || str_contains($up, 'CLICHE')) {
            return self::RILIEVO;
        }
        if (str_contains($up, 'TRANCIA')) {
            return self::TRANCIATURA;
        }
        if (str_contains($up, 'ROTATIV') || str_contains($up, 'CILINDRIC')) {
            return self::ROTATIVA;
        }
        // Default per "FUST*", "BOBST*", "FUSTELLA*"
        return self::PIANA;
    }

    /**
     * Macchine compatibili con il tipo (id macchina come da MacchinaRegistry).
     *
     * @return list<string>
     */
    public function macchineCompatibili(): array
    {
        return match ($this) {
            self::PIANA => ['BOBST'],
            self::ROTATIVA => ['BOBST_ROT'],
            self::TRANCIATURA => ['BOBST', 'ZUND'],
            self::RILIEVO => ['JOH', 'BOBST'],
        };
    }
}
