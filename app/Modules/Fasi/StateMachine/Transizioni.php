<?php

declare(strict_types=1);

namespace App\Modules\Fasi\StateMachine;

use App\Modules\Fasi\Exceptions\FaseTransitionException;

/**
 * Registry statico delle transizioni canoniche del dominio fasi.
 *
 * Stati numerici:
 *   0 NON_INIZIATA, 1 PRONTA, 2 AVVIATA, 3 TERMINATA, 4 CONSEGNATA, 5 ESTERNO
 *
 * Stati stringa (non enumerabili):
 *   'EXT'      = inviata a fornitore esterno
 *   '<motivo>' = qualsiasi altra stringa = motivo di pausa quando la fase
 *                era avviata (es. 'attesa carta', 'guasto macchina')
 */
final class Transizioni
{
    /**
     * Matrice canonica delle transizioni numeriche ammesse.
     * Chiave = stato origine, valori = stati destinazione consentiti.
     *
     * @var array<int, list<int>>
     */
    private const MATRICE = [
        0 => [1],          // NON_INIZIATA -> PRONTA
        1 => [2],          // PRONTA -> AVVIATA
        2 => [3, 5],       // AVVIATA -> TERMINATA | ESTERNO  (+ pausa via stringa, gestita a parte)
        3 => [4, 2],       // TERMINATA -> CONSEGNATA | riapertura AVVIATA (Prinect/Fiery)
        4 => [],           // CONSEGNATA = stato finale
        5 => [3],          // ESTERNO -> TERMINATA (rientro da fornitore)
    ];

    private function __construct() {}

    /**
     * Verifica se la transizione è valida senza sollevare eccezioni.
     */
    public static function isValida(int|string $da, int|string $a): bool
    {
        // 2 -> stringa libera (NON 'EXT', NON numerica) = pausa con motivo
        if (self::isNumerico($da) && (int) $da === 2 && self::isMotivoPausa($a)) {
            return true;
        }

        // stringa pausa -> 2 = ripresa
        if (self::isMotivoPausa($da) && self::isNumerico($a) && (int) $a === 2) {
            return true;
        }

        // 'EXT' è alias backward-compat di stato 5
        if (self::normalizza($da) === 5 && self::isNumerico($a) && (int) $a === 3) {
            return true;
        }
        if (self::isNumerico($da) && (int) $da === 2 && self::normalizza($a) === 5) {
            return true;
        }

        if (! self::isNumerico($da) || ! self::isNumerico($a)) {
            return false;
        }

        $daInt = (int) $da;
        $aInt  = (int) $a;

        return in_array($aInt, self::MATRICE[$daInt] ?? [], true);
    }

    /**
     * Valida la transizione, lancia FaseTransitionException se invalida.
     */
    public static function validate(int|string $da, int|string $a): bool
    {
        if (! self::isValida($da, $a)) {
            throw FaseTransitionException::nonAmmessa($da, $a);
        }

        return true;
    }

    /**
     * True se il valore è numerico-puro (int o string castabile).
     */
    private static function isNumerico(int|string $v): bool
    {
        return is_int($v) || (is_string($v) && ctype_digit($v));
    }

    /**
     * True se la stringa rappresenta un motivo di pausa (qualsiasi
     * stringa non numerica e diversa da 'EXT').
     */
    private static function isMotivoPausa(int|string $v): bool
    {
        return is_string($v) && ! ctype_digit($v) && strtoupper($v) !== 'EXT';
    }

    /**
     * Normalizza 'EXT' -> 5 per uniformare la matrice.
     */
    private static function normalizza(int|string $v): int|string
    {
        if (is_string($v) && strtoupper($v) === 'EXT') {
            return 5;
        }

        return self::isNumerico($v) ? (int) $v : $v;
    }
}
