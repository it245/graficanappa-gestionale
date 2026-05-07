<?php

declare(strict_types=1);

namespace App\Modules\Fasi\StateMachine;

/**
 * Contratto per regole di transizione custom componibili nel registry.
 *
 * I valori di stato accettati sono `int` (0..5) oppure `string`
 * (es. 'EXT' o motivi pausa testuali).
 */
interface TransizioneRule
{
    /**
     * Restituisce true se la regola autorizza la transizione $da -> $a.
     */
    public function check(int|string $da, int|string $a): bool;

    /**
     * Messaggio descrittivo per audit / errori utente.
     */
    public function getMessaggio(): string;
}
