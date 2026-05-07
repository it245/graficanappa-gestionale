<?php

declare(strict_types=1);

namespace App\Modules\Operatori\Permessi;

use App\Modules\Operatori\Enums\RuoloOperatore;

/**
 * Matrice statica Ruolo -> Permessi consentiti.
 *
 * NB: per Operatore i permessi AVVIA_FASE/TERMINA_FASE sono concessi a livello "ruolo",
 * ma la verifica di appartenenza al reparto della fase resta a carico
 * di AssegnazioneFaseRule (controllo contestuale).
 */
final class PermessiMatrix
{
    /**
     * @return array<string, list<Permesso>>
     */
    private static function matrix(): array
    {
        return [
            RuoloOperatore::Admin->value => [
                Permesso::AVVIA_FASE,
                Permesso::TERMINA_FASE,
                Permesso::MODIFICA_PRIORITA,
                Permesso::SCARICO_CARTA,
                Permesso::GESTISCE_DDT,
                Permesso::CREA_OPERATORE,
                Permesso::MODIFICA_FASE_REPARTO_ALTRO,
            ],
            RuoloOperatore::Owner->value => [
                Permesso::AVVIA_FASE,
                Permesso::TERMINA_FASE,
                Permesso::MODIFICA_PRIORITA,
                Permesso::SCARICO_CARTA,
                Permesso::GESTISCE_DDT,
                Permesso::MODIFICA_FASE_REPARTO_ALTRO,
                // niente CREA_OPERATORE — solo Admin
            ],
            RuoloOperatore::Operatore->value => [
                Permesso::AVVIA_FASE,
                Permesso::TERMINA_FASE,
                // sul SOLO proprio reparto — verificato da AssegnazioneFaseRule
            ],
            RuoloOperatore::Magazzino->value => [
                Permesso::SCARICO_CARTA,
            ],
            RuoloOperatore::Spedizione->value => [
                Permesso::GESTISCE_DDT,
            ],
        ];
    }

    public static function has(RuoloOperatore $ruolo, Permesso $permesso): bool
    {
        $consentiti = self::matrix()[$ruolo->value] ?? [];

        foreach ($consentiti as $p) {
            if ($p === $permesso) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Permesso>
     */
    public static function permessiPer(RuoloOperatore $ruolo): array
    {
        return self::matrix()[$ruolo->value] ?? [];
    }
}
