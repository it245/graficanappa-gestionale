<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Rules;

use App\Modules\Reparti\Enums\CodiceReparto;

/**
 * Regola pura: dato un codice macchina (XL106, JOH, V900, BOBST, PI01,
 * ZUND, etc.) restituisce il reparto a cui appartiene.
 *
 * Usato dallo scheduler Mossa 37 per validare che un job assegnato a una
 * macchina sia coerente con il reparto della fase.
 */
final class CompatibilitaMacchinaReparto
{
    /**
     * Mappa macchina → reparto.
     *
     * Le chiavi seguono la nomenclatura informale di reparto (NON sono
     * codici fase). Match case-insensitive.
     *
     * @return array<string, CodiceReparto>
     */
    private static function mappa(): array
    {
        return [
            // Stampa Offset
            'XL106'      => CodiceReparto::STAMPA_OFFSET,
            'XL'         => CodiceReparto::STAMPA_OFFSET,
            'HEIDELBERG' => CodiceReparto::STAMPA_OFFSET,

            // Digitale
            'V900'       => CodiceReparto::DIGITALE,
            'INDIGO'     => CodiceReparto::DIGITALE,
            'CANON'      => CodiceReparto::DIGITALE,
            'MGI'        => CodiceReparto::DIGITALE,
            'ZUND'       => CodiceReparto::DIGITALE,

            // Stampa a caldo
            'JOH'        => CodiceReparto::STAMPA_A_CALDO,

            // Plastificazione
            'PLAST'      => CodiceReparto::PLASTIFICAZIONE,
            'BV'         => CodiceReparto::PLASTIFICAZIONE,

            // Fustella
            'BOBST'      => CodiceReparto::FUSTELLA,
            'IML'        => CodiceReparto::FUSTELLA,
            'STARPACK'   => CodiceReparto::FUSTELLA,

            // Piegaincolla
            'PI01'       => CodiceReparto::PIEGAINCOLLA,
            'PI02'       => CodiceReparto::PIEGAINCOLLA,
            'PI03'       => CodiceReparto::PIEGAINCOLLA,

            // Legatoria
            'TAGLIACARTE'=> CodiceReparto::LEGATORIA,
            'POLAR'      => CodiceReparto::LEGATORIA,

            // Spedizione
            'BRT'        => CodiceReparto::SPEDIZIONE,
        ];
    }

    /**
     * Reparto della macchina, null se ignota.
     */
    public static function reparto(string $codiceMacchina): ?CodiceReparto
    {
        $needle = strtoupper(trim($codiceMacchina));
        foreach (self::mappa() as $k => $v) {
            if (strtoupper($k) === $needle) {
                return $v;
            }
        }
        // Match prefisso (es. "XL106-1" → XL106)
        foreach (self::mappa() as $k => $v) {
            if (str_starts_with($needle, strtoupper($k))) {
                return $v;
            }
        }
        return null;
    }

    /**
     * True se la macchina è compatibile col reparto specificato.
     */
    public static function isCompatibile(string $codiceMacchina, CodiceReparto $reparto): bool
    {
        return self::reparto($codiceMacchina) === $reparto;
    }

    /**
     * Tutte le macchine note di un reparto (utile per dashboard owner).
     *
     * @return list<string>
     */
    public static function macchineDi(CodiceReparto $reparto): array
    {
        return array_keys(array_filter(
            self::mappa(),
            static fn (CodiceReparto $r) => $r === $reparto
        ));
    }
}
