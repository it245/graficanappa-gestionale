<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Enums;

/**
 * Tipologie di stampa supportate dal modulo.
 *
 * Mappa i reparti/macchine fisici di Grafica Nappa:
 *  - Offset    → Heidelberg XL106 (Prinect)
 *  - Digitale  → Canon V900 + HP Indigo (Fiery)
 *  - Caldo     → JOH (manuale, no API)
 *  - Foil      → stampa a caldo con foil/lamine
 *  - Plotter   → futuro plotter taglio/stampa grande formato
 */
enum TipoStampa: string
{
    case Offset   = 'offset';
    case Digitale = 'digitale';
    case Caldo    = 'caldo';
    case Foil     = 'foil';
    case Plotter  = 'plotter';

    public function label(): string
    {
        return match ($this) {
            self::Offset   => 'Stampa Offset (XL106)',
            self::Digitale => 'Stampa Digitale (V900/Indigo)',
            self::Caldo    => 'Stampa a Caldo (JOH)',
            self::Foil     => 'Foil / Lamina',
            self::Plotter  => 'Plotter',
        };
    }

    /**
     * Indica se la tipologia è gestita via integrazione API
     * (true) oppure è puramente manuale (false).
     */
    public function haIntegrazione(): bool
    {
        return match ($this) {
            self::Offset, self::Digitale => true,
            self::Caldo, self::Foil, self::Plotter => false,
        };
    }
}
