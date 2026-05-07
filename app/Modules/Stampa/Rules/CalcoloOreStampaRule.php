<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Rules;

use App\Modules\Stampa\Enums\TipoStampa;
use InvalidArgumentException;

/**
 * Regola di calcolo delle ore macchina necessarie per una tiratura.
 *
 * Formula:
 *   ore = (avviamentoMinuti / 60) + (copie / capacitaOraria)
 *
 * Capacità nominali (fogli/ora) in funzione del tipo di stampa:
 *  - Offset    XL106  → 4500
 *  - Digitale  V900   → 800
 *  - Digitale  Indigo → 200 (default per "digitale" se non specificato)
 *  - Caldo     JOH    → 1500
 *  - Foil              →  600 (stima)
 *  - Plotter           →  120 (stima)
 *
 * Per ora la classe usa Indigo (200/h) come default conservativo per
 * il tipo Digitale; il caller può comunque ricalcolare passando una
 * capacità custom tramite override (vedi calcolaOreCapacita()).
 *
 * NOTA: pure class senza dipendenze, tipi stretti.
 */
final class CalcoloOreStampaRule
{
    /** Capacità oraria nominale (fogli/ora) per tipologia. */
    private const CAPACITA_ORARIA = [
        TipoStampa::Offset->value   => 4500,
        TipoStampa::Digitale->value => 200,  // default Indigo
        TipoStampa::Caldo->value    => 1500,
        TipoStampa::Foil->value     => 600,
        TipoStampa::Plotter->value  => 120,
    ];

    public function __construct(
        private readonly AvviamentoRule $avviamento = new AvviamentoRule(),
    ) {
    }

    /**
     * Calcola le ore necessarie a stampare $copie fogli del tipo dato.
     *
     * @param  int         $copie           Numero di fogli da produrre (>=0).
     * @param  TipoStampa  $tipo            Tipologia di stampa.
     * @param  int         $coloriPerLato   Numero colori per lato (oggi
     *                                      non altera la capacità nominale,
     *                                      ma è esposto per future regole
     *                                      "tempo cambio lastra" su offset).
     */
    public function calcolaOre(int $copie, TipoStampa $tipo, int $coloriPerLato = 4): float
    {
        if ($copie < 0) {
            throw new InvalidArgumentException('Copie non può essere negativo.');
        }
        if ($coloriPerLato < 1 || $coloriPerLato > 8) {
            throw new InvalidArgumentException('Colori per lato fuori range (1-8).');
        }

        $capacita = self::CAPACITA_ORARIA[$tipo->value];
        $oreAvviamento = $this->avviamento->tempoAvviamento($tipo) / 60.0;
        $oreEsecuzione = $copie / $capacita;

        return round($oreAvviamento + $oreEsecuzione, 3);
    }

    /**
     * Variante che accetta una capacità oraria custom (es. 800 per V900
     * vs 200 per Indigo) preservando la stessa formula.
     */
    public function calcolaOreCapacita(int $copie, TipoStampa $tipo, int $capacitaOraria): float
    {
        if ($capacitaOraria <= 0) {
            throw new InvalidArgumentException('Capacità oraria deve essere > 0.');
        }
        if ($copie < 0) {
            throw new InvalidArgumentException('Copie non può essere negativo.');
        }

        $oreAvviamento = $this->avviamento->tempoAvviamento($tipo) / 60.0;
        $oreEsecuzione = $copie / $capacitaOraria;

        return round($oreAvviamento + $oreEsecuzione, 3);
    }
}
