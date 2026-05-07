<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Enums;

/**
 * Livelli di priorità del sistema di scheduling Mossa 37.
 *
 * L'ordine numerico riflette la PRECEDENZA di valutazione: a parità
 * di livello superiore, si discrimina con quello inferiore.
 *
 *  1 — Disponibilita  : la fase è eseguibile (predecessori terminati)
 *  2 — Urgenza        : prossimità (o ritardo) sulla data di consegna
 *  3 — BatchAffinity  : affinità di lotto (carta/reparto/finestra ±5gg)
 *  4 — SequenzaCiclo  : posizione nel ciclo produttivo (config/fasi_priorita)
 */
enum LivelloPriorita: int
{
    case Disponibilita  = 1;
    case Urgenza        = 2;
    case BatchAffinity  = 3;
    case SequenzaCiclo  = 4;

    public function label(): string
    {
        return match ($this) {
            self::Disponibilita => 'Disponibilità',
            self::Urgenza       => 'Urgenza',
            self::BatchAffinity => 'Affinità Batch',
            self::SequenzaCiclo => 'Sequenza Ciclo',
        };
    }

    /**
     * Peso moltiplicativo del livello nella combinazione lineare
     * di {@see PrioritaService::calcolaPriorita()}.
     */
    public function peso(): float
    {
        return match ($this) {
            self::Disponibilita => 1_000_000.0,
            self::Urgenza       => 10_000.0,
            self::BatchAffinity => 100.0,
            self::SequenzaCiclo => 1.0,
        };
    }
}
