<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Templates;

use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;

/**
 * Template: scorta carta sotto soglia minima.
 *
 * Context atteso:
 *   - codice       (string)  es. 'PAT250'
 *   - descrizione  (string)  es. 'Patinata Lucida 250g 70x100'
 *   - giacenza     (int|float) bancali/fogli rimasti
 *   - soglia       (int|float) soglia configurata
 *   - destinatario (mixed)
 *   - canale       (CanaleNotifica, opz., default Telegram)
 *   - priorita     (PrioritaNotifica, opz., calcolata su giacenza/soglia)
 *
 * Esempio:
 *   $n = SottoSogliaCarta::genera([
 *       'codice' => 'PAT250',
 *       'descrizione' => 'Patinata Lucida 250g 70x100',
 *       'giacenza' => 2,
 *       'soglia' => 5,
 *       'destinatario' => env('TELEGRAM_MAGAZZINO_CHAT_ID'),
 *   ]);
 */
final class SottoSogliaCarta
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function genera(array $context): Notifica
    {
        $codice = (string) ($context['codice'] ?? '?');
        $descr = (string) ($context['descrizione'] ?? '');
        $giac = (float) ($context['giacenza'] ?? 0);
        $soglia = (float) ($context['soglia'] ?? 0);

        $titolo = "Carta sotto soglia: {$codice}";
        $msg = trim("{$descr}\nGiacenza: {$giac} (soglia {$soglia})");

        // Priorita' dinamica: piu' siamo vicini a 0, piu' urgente.
        $priorita = $context['priorita'] ?? match (true) {
            $giac <= 0 => PrioritaNotifica::Critica,
            $soglia > 0 && $giac / max($soglia, 1) <= 0.3 => PrioritaNotifica::Alta,
            default => PrioritaNotifica::Normale,
        };

        return new Notifica(
            titolo: $titolo,
            messaggio: $msg,
            canale: $context['canale'] ?? CanaleNotifica::Telegram,
            priorita: $priorita,
            destinatario: $context['destinatario'] ?? null,
            payload: [
                'tipo' => 'sotto_soglia_carta',
                'codice' => $codice,
                'giacenza' => $giac,
                'soglia' => $soglia,
            ],
        );
    }
}
