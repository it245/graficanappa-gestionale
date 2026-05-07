<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Rules;

/**
 * Verifica che un operatore sia abilitato a lavorare in un dato reparto.
 *
 * Coordina con `App\Modules\Reparti\Enums\CodiceReparto` e con la
 * `operatori.reparto_id` (single-reparto) — quando l'operatore deve
 * coprire più reparti viene gestito tramite `operatori_reparti` pivot
 * (vedi modulo Reparti). Qui esponiamo la rule pura.
 */
final class CompatibilitaTurnoReparto
{
    /**
     * @param array<int, string> $repartiAbilitati codici reparto autorizzati per l'operatore
     */
    public function permessa(string $repartoTurno, array $repartiAbilitati): bool
    {
        return in_array($repartoTurno, $repartiAbilitati, true);
    }

    public function motivoSeNon(string $repartoTurno, array $repartiAbilitati): ?string
    {
        if ($this->permessa($repartoTurno, $repartiAbilitati)) {
            return null;
        }
        return "Operatore non abilitato al reparto '{$repartoTurno}' (abilitato: "
            . implode(',', $repartiAbilitati) . ')';
    }
}
