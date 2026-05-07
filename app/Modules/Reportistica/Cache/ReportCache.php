<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Wrapper Cache con chiavi e TTL standardizzati per il modulo Reportistica.
 *
 * Centralizzare i nomi qui evita typo (`reporto_ore` vs `report_ore`) e
 * permette di invalidare gruppi coerenti dal listener
 * {@see \App\Modules\Reportistica\Listeners\InvalidaReportistica}.
 *
 * TTL deliberatamente diversi:
 *  - KPI live          → 5 min (header dashboard, alta volatilità)
 *  - Report ore        → 15 min (raggruppamento commesse, costo query alto)
 *  - Panoramica reparti → 10 min (aggregato medio)
 */
final class ReportCache
{
    public const TTL_KPI         = 300;   // 5 min
    public const TTL_REPORT_ORE  = 900;   // 15 min
    public const TTL_PANORAMICA  = 600;   // 10 min

    public const KEY_KPI         = 'reportistica.kpi.dashboard';
    public const KEY_REPORT_ORE  = 'reportistica.report_ore';
    public const KEY_PANORAMICA  = 'reportistica.panoramica_reparti';
    public const KEY_FUSTELLE    = 'reportistica.fustelle_overview';
    public const KEY_ESTERNE     = 'reportistica.esterne_overview';

    /**
     * @template T
     * @param  callable(): T $cb
     * @return T
     */
    public static function remember(string $key, int $ttl, callable $cb): mixed
    {
        return Cache::remember($key, $ttl, $cb);
    }

    /**
     * Invalida tutte le chiavi che dipendono dallo stato delle fasi.
     * Chiamata dal listener su FaseAggiornata / PhaseCompleted.
     */
    public static function invalidaTutto(): void
    {
        foreach ([
            self::KEY_KPI,
            self::KEY_REPORT_ORE,
            self::KEY_PANORAMICA,
            self::KEY_FUSTELLE,
            self::KEY_ESTERNE,
        ] as $k) {
            Cache::forget($k);
        }
    }
}
