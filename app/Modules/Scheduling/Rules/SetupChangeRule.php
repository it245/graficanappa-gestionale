<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Rules;

use App\Models\OrdineFase;

/**
 * Regola sui cambi-configurazione (setup) macchina.
 *
 * Macchine con setup non trascurabile (Mossa 37):
 *   - BOBST       : 2 config (rilievi seq39 / fustelle seq40), cambio 1h
 *   - PIEGAINCOLLA: 3 config (PI01, PI02, PI03), cambio 1h
 *
 * Per le altre macchine il cambio è considerato 0 ore
 * (lo scheduler ignora il setup).
 */
final class SetupChangeRule
{
    public const ORE_CAMBIO_DEFAULT = 1.0;

    /** Macchine con setup significativo (chiave canonica uppercase). */
    private const MACCHINE_CON_SETUP = ['BOBST', 'PIEGAINCOLLA'];

    public static function richiedeCambio(
        OrdineFase $a,
        OrdineFase $b,
        string $macchina,
    ): bool {
        $m = strtoupper($macchina);
        if (! in_array($m, self::MACCHINE_CON_SETUP, true)) {
            return false;
        }

        return self::configurazione($a, $m) !== self::configurazione($b, $m);
    }

    public static function oreCambio(string $macchina): float
    {
        $m = strtoupper($macchina);
        return in_array($m, self::MACCHINE_CON_SETUP, true)
            ? self::ORE_CAMBIO_DEFAULT
            : 0.0;
    }

    /**
     * Estrae la "configurazione" della fase per la macchina data.
     *
     * BOBST        : "RILIEVI" se la fase è di sequenza 39 (rilievi), "FUSTELLE" altrimenti.
     * PIEGAINCOLLA : prefisso PI01/PI02/PI03 dal nome fase, fallback "PI".
     */
    private static function configurazione(OrdineFase $fase, string $macchina): string
    {
        $nome = strtoupper((string) $fase->fase);

        return match ($macchina) {
            'BOBST'        => str_contains($nome, 'RILIEV') || str_contains($nome, 'STAMPASECCO')
                                ? 'RILIEVI'
                                : 'FUSTELLE',
            'PIEGAINCOLLA' => match (true) {
                str_starts_with($nome, 'PI01') => 'PI01',
                str_starts_with($nome, 'PI02') => 'PI02',
                str_starts_with($nome, 'PI03') => 'PI03',
                default                        => 'PI',
            },
            default        => 'STD',
        };
    }
}
