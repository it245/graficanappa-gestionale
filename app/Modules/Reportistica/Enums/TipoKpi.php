<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Enums;

/**
 * Tipologie KPI standard mostrate nelle dashboard admin/owner.
 *
 * Il `value` fa da chiave stabile per cache, payload JSON e selettori
 * frontend (Chart.js / data-attribute). Aggiungere un caso QUI implica:
 *  1. Wiring in {@see \App\Modules\Reportistica\Services\KpiService::tutti()}
 *  2. Eventuale invalidazione cache (vedi {@see \App\Modules\Reportistica\Cache\ReportCache})
 */
enum TipoKpi: string
{
    case COMMESSE_ATTIVE   = 'commesse_attive';
    case FASI_OGGI         = 'fasi_oggi';
    case ORE_SETTIMANA     = 'ore_settimana';
    case EFFICIENZA        = 'efficienza';
    case PUNTUALITA        = 'puntualita';
    case SCARTO_PRINECT    = 'scarto_prinect';

    public function label(): string
    {
        return match ($this) {
            self::COMMESSE_ATTIVE => 'Commesse attive',
            self::FASI_OGGI       => 'Fasi terminate oggi',
            self::ORE_SETTIMANA   => 'Ore lavorate (7gg)',
            self::EFFICIENZA      => 'Efficienza',
            self::PUNTUALITA      => 'Tasso puntualità',
            self::SCARTO_PRINECT  => 'Scarto stampa offset',
        };
    }

    /**
     * Unità di misura per il rendering (suffisso/prefisso UI).
     */
    public function unita(): string
    {
        return match ($this) {
            self::COMMESSE_ATTIVE, self::FASI_OGGI => '',
            self::ORE_SETTIMANA                    => 'h',
            default                                => '%',
        };
    }
}
