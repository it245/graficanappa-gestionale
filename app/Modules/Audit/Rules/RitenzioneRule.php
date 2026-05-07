<?php

declare(strict_types=1);

namespace App\Modules\Audit\Rules;

use App\Modules\Audit\Enums\LivelloSicurezza;
use Carbon\CarbonImmutable;

/**
 * Politiche di ritenzione log audit (GDPR + procedura sindacale).
 *
 * Riferimenti normativi:
 *  - GDPR art. 5(1)(e): conservare per il tempo strettamente necessario
 *  - Statuto Lavoratori art. 4: log produttività individuale richiedono
 *    accordo RSU/RSA prima dell'utilizzo retroattivo
 *  - Provvedimento Garante 27/11/2008 (amministratori di sistema): 6 mesi min.
 *
 * Default per livello (vedi LivelloSicurezza::giorniRitenzione()):
 *  - Normale:   3 anni
 *  - Sensibile: 2 anni
 *  - Critico:   7 anni
 */
final class RitenzioneRule
{
    /**
     * Restituisce true se il log è oltre la finestra di ritenzione e va
     * cancellato dal job di pulizia (vedi PulisciAuditLog command).
     */
    public static function eScaduto(
        CarbonImmutable $createdAt,
        LivelloSicurezza $livello,
        ?CarbonImmutable $now = null,
    ): bool {
        $now = $now ?? CarbonImmutable::now();
        $limite = $createdAt->addDays($livello->giorniRitenzione());
        return $now->greaterThan($limite);
    }

    /**
     * Data di scadenza calcolata. Utile per dashboard "log in scadenza".
     */
    public static function dataScadenza(
        CarbonImmutable $createdAt,
        LivelloSicurezza $livello,
    ): CarbonImmutable {
        return $createdAt->addDays($livello->giorniRitenzione());
    }
}
