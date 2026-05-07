<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Templates;

use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;

/**
 * Template: ritardo spedizione DDT/BRT.
 *
 * Context atteso:
 *   - ddt        (string)        es. '12345'
 *   - cliente    (string)        ragione sociale
 *   - giorni     (int)           giorni di ritardo
 *   - destinatario (mixed)       chat_id|email|Operatore
 *   - canale     (CanaleNotifica, opz., default Telegram)
 *   - priorita   (PrioritaNotifica, opz., default Alta)
 *
 * Esempio:
 *   $n = RitardoSpedizione::genera([
 *       'ddt' => '12345',
 *       'cliente' => 'ACME Srl',
 *       'giorni' => 3,
 *       'destinatario' => $owner,
 *   ]);
 *   $service->inviaSuCanale($n, $n->canale);
 */
final class RitardoSpedizione
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function genera(array $context): Notifica
    {
        $ddt = (string) ($context['ddt'] ?? '?');
        $cliente = (string) ($context['cliente'] ?? '?');
        $giorni = (int) ($context['giorni'] ?? 0);

        $titolo = "Ritardo spedizione DDT {$ddt}";
        $msg = "DDT {$ddt} per {$cliente} in ritardo di {$giorni} "
            .($giorni === 1 ? 'giorno' : 'giorni').'.';

        $canale = $context['canale'] ?? CanaleNotifica::Telegram;
        $priorita = $context['priorita'] ?? ($giorni >= 5 ? PrioritaNotifica::Critica : PrioritaNotifica::Alta);

        return new Notifica(
            titolo: $titolo,
            messaggio: $msg,
            canale: $canale,
            priorita: $priorita,
            destinatario: $context['destinatario'] ?? null,
            payload: [
                'tipo' => 'ritardo_spedizione',
                'ddt' => $ddt,
                'cliente' => $cliente,
                'giorni' => $giorni,
            ],
        );
    }
}
