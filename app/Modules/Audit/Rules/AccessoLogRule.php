<?php

declare(strict_types=1);

namespace App\Modules\Audit\Rules;

use App\Modules\Audit\Enums\LivelloSicurezza;

/**
 * Chi può leggere/esportare i log di audit.
 *
 * Politica MES Grafica Nappa:
 *  - Owner (capo Vittorio): legge tutti i log NORMALE; ridotto su SENSIBILE/CRITICO.
 *  - Admin (Giovanni / IT): legge tutti i livelli (responsabile sicurezza/DPO de facto).
 *  - Operatore: legge SOLO i propri log (GDPR art. 15 — diritto di accesso).
 *
 * NB: L'enforcement reale va fatto a livello di Controller/Policy Laravel.
 * Questa Rule fornisce la logica pura — riusabile da ComplianceExportService
 * e da middleware admin.
 */
final class AccessoLogRule
{
    /** @var list<string> ruoli abilitati a vedere log NORMALE */
    private const RUOLI_LETTURA_NORMALE = ['admin', 'owner'];

    /** @var list<string> ruoli abilitati a vedere log SENSIBILE/CRITICO */
    private const RUOLI_LETTURA_SENSIBILE = ['admin'];

    /**
     * @param string|null $ruoloUtente es. 'admin', 'owner', 'operatore', null
     * @param int|null $userIdLog il `user_id` del log che si vuole leggere
     * @param int|null $userIdRichiedente l'utente che chiede di leggere
     */
    public static function puoLeggere(
        ?string $ruoloUtente,
        LivelloSicurezza $livello,
        ?int $userIdLog,
        ?int $userIdRichiedente,
    ): bool {
        $ruolo = strtolower((string) $ruoloUtente);

        // Operatore: solo i propri log e solo livello normale
        if ($userIdLog !== null
            && $userIdRichiedente !== null
            && $userIdLog === $userIdRichiedente
            && $livello === LivelloSicurezza::Normale
        ) {
            return true;
        }

        if ($livello === LivelloSicurezza::Normale) {
            return in_array($ruolo, self::RUOLI_LETTURA_NORMALE, true);
        }

        return in_array($ruolo, self::RUOLI_LETTURA_SENSIBILE, true);
    }

    /**
     * Solo admin può esportare log per terzi (RSU, ITL, magistratura).
     */
    public static function puoEsportare(?string $ruoloUtente): bool
    {
        return strtolower((string) $ruoloUtente) === 'admin';
    }
}
