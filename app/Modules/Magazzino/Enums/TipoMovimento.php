<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Enums;

/**
 * Tipologia di movimento di magazzino.
 *
 * I valori string rispecchiano l'enum della colonna `magazzino_movimenti.tipo`
 * più "inventario" usato per le rettifiche da conta fisica periodica.
 *
 * Convenzione segno (delta giacenza):
 *  - Carico, Reso, Inventario(positivo)  -> +qta
 *  - Scarico                              -> -qta
 *  - Rettifica                            -> ±qta (segnato)
 *
 * @example
 *  $tipo = TipoMovimento::from('carico');
 *  if ($tipo->aumentaGiacenza()) { ... }
 */
enum TipoMovimento: string
{
    case Carico     = 'carico';
    case Scarico    = 'scarico';
    case Rettifica  = 'rettifica';
    case Inventario = 'inventario';
    case Reso       = 'reso';

    /**
     * Etichetta leggibile per UI italiana.
     */
    public function label(): string
    {
        return match ($this) {
            self::Carico     => 'Carico fornitore',
            self::Scarico    => 'Scarico produzione',
            self::Rettifica  => 'Rettifica',
            self::Inventario => 'Inventario',
            self::Reso       => 'Reso a fornitore',
        };
    }

    /**
     * True se il movimento incrementa la giacenza per default.
     * (Rettifica esclusa: dipende dal segno della quantità.)
     */
    public function aumentaGiacenza(): bool
    {
        return match ($this) {
            self::Carico, self::Reso, self::Inventario => true,
            self::Scarico                              => false,
            self::Rettifica                            => false,
        };
    }

    /**
     * True se il tipo richiede ammontare strettamente positivo.
     * Rettifica/Inventario possono accettare segno.
     */
    public function richiedeQuantitaPositiva(): bool
    {
        return $this === self::Carico
            || $this === self::Scarico
            || $this === self::Reso;
    }
}
