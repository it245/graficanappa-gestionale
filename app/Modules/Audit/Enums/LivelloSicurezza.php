<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

/**
 * Livello di sensibilità del log.
 *
 * - Normale: eventi business standard (avvio/termine fase, sync Onda).
 *            Visibili a owner + admin, ritenzione standard (3 anni GDPR).
 *
 * - Sensibile: dati personali (login operatore, accesso a CV, modifica
 *              anagrafica). Visibili solo ad admin, mascheratura più
 *              aggressiva, ritenzione GDPR ridotta.
 *
 * - Critico: cambio permessi, impersonation, export massivo, accesso log
 *            stessi. Notifica immediata DPO + ritenzione massima.
 */
enum LivelloSicurezza: string
{
    case Normale   = 'normale';
    case Sensibile = 'sensibile';
    case Critico   = 'critico';

    public function label(): string
    {
        return match ($this) {
            self::Normale   => 'Normale',
            self::Sensibile => 'Sensibile',
            self::Critico   => 'Critico',
        };
    }

    /**
     * Giorni di ritenzione consigliati per il livello.
     * Usato da {@see RitenzioneRule}.
     */
    public function giorniRitenzione(): int
    {
        return match ($this) {
            self::Normale   => 1095, // 3 anni
            self::Sensibile => 730,  // 2 anni (minimizzazione GDPR)
            self::Critico   => 2555, // 7 anni (compliance + dispute legali)
        };
    }
}
