<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Rules;

use App\Modules\Presenze\ValueObjects\OreLavorate;

/**
 * Determina la quota di straordinari su un monte ore settimanale.
 *
 * CCNL Grafici Editoriali — riferimento interno (la soglia esatta va
 * confermata con consulente del lavoro):
 *  - Settimana standard: 40h
 *  - Oltre 40h e fino a 48h: straordinario "diurno" (+25%)
 *  - Oltre 48h: straordinario "festivo/oltre" (+35%)
 *
 * Questa rule è puro calcolo: non scrive su DB, non fa I/O. Usata da
 * AssenzeService::calcolaStraordinari (futuro) e per dispatch evento
 * StraordinariSuperati nel TimbratureSyncService.
 */
final class CalcoloStraordinariRule
{
    public const SOGLIA_SETTIMANALE_MIN = 40 * 60; // 2400 min
    public const SOGLIA_FESTIVO_MIN = 48 * 60;     // 2880 min

    /**
     * @return array{ordinarie: OreLavorate, straord_25: OreLavorate, straord_35: OreLavorate}
     */
    public function suddividi(OreLavorate $totaleSettimana): array
    {
        $tot = $totaleSettimana->minutiTotali;
        $ordinarie = min($tot, self::SOGLIA_SETTIMANALE_MIN);
        $straord25 = max(0, min($tot, self::SOGLIA_FESTIVO_MIN) - self::SOGLIA_SETTIMANALE_MIN);
        $straord35 = max(0, $tot - self::SOGLIA_FESTIVO_MIN);

        return [
            'ordinarie'  => OreLavorate::daMinuti($ordinarie),
            'straord_25' => OreLavorate::daMinuti($straord25),
            'straord_35' => OreLavorate::daMinuti($straord35),
        ];
    }

    public function haStraordinari(OreLavorate $tot): bool
    {
        return $tot->minutiTotali > self::SOGLIA_SETTIMANALE_MIN;
    }
}
