<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Rules;

use App\Models\Operatore;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;

/**
 * Regola di routing canale -> priorita'.
 *
 * Single Source of Truth per "dove finisce" una notifica.
 * Ritorna SEMPRE almeno un canale; l'ordine nell'array e' significativo
 * (il NotificaService li tenta nell'ordine, raccoglie tutti i risultati).
 *
 * Convenzione attuale:
 *  - Critica  -> Telegram + Email + BrowserPush  (fan-out massimo)
 *  - Alta     -> Telegram + BrowserPush
 *  - Normale  -> Telegram                         (default operativo)
 *  - Bassa    -> BrowserPush                      (silenziosa, non disturba)
 *
 * Override: se l'operatore non ha chat_id Telegram, esclude Telegram.
 * Se non ha email, esclude Email.
 */
final class CanaleSceltaRule
{
    /**
     * @return list<CanaleNotifica>
     */
    public function scegliCanali(PrioritaNotifica $p, ?Operatore $dest = null): array
    {
        $canali = match ($p) {
            PrioritaNotifica::Critica => [
                CanaleNotifica::Telegram,
                CanaleNotifica::Email,
                CanaleNotifica::BrowserPush,
            ],
            PrioritaNotifica::Alta => [
                CanaleNotifica::Telegram,
                CanaleNotifica::BrowserPush,
            ],
            PrioritaNotifica::Normale => [CanaleNotifica::Telegram],
            PrioritaNotifica::Bassa => [CanaleNotifica::BrowserPush],
        };

        if ($dest !== null) {
            $canali = $this->filtraPerCapacitaDestinatario($canali, $dest);
        }

        // Fallback minimo: se per qualche ragione filtrato a zero, usa BrowserPush.
        return $canali === [] ? [CanaleNotifica::BrowserPush] : array_values($canali);
    }

    /**
     * Helper "primo canale" per chi vuole singolo.
     */
    public function scegliCanale(PrioritaNotifica $p, ?Operatore $dest = null): CanaleNotifica
    {
        return $this->scegliCanali($p, $dest)[0];
    }

    /**
     * @param  list<CanaleNotifica>  $canali
     * @return list<CanaleNotifica>
     */
    private function filtraPerCapacitaDestinatario(array $canali, Operatore $op): array
    {
        $hasChatId = ! empty($op->telegram_chat_id ?? null);
        $hasEmail = is_string($op->email ?? null) && $op->email !== '';

        return array_values(array_filter($canali, function (CanaleNotifica $c) use ($hasChatId, $hasEmail): bool {
            return match ($c) {
                CanaleNotifica::Telegram => $hasChatId,
                CanaleNotifica::Email => $hasEmail,
                default => true,
            };
        }));
    }
}
