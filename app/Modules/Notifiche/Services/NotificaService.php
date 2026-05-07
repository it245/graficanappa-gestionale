<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Services;

use App\Models\Operatore;
use App\Modules\Notifiche\Contracts\NotificaSenderInterface;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;
use App\Modules\Notifiche\Rules\CanaleSceltaRule;
use App\Modules\Notifiche\ValueObjects\Notifica;
use Illuminate\Support\Facades\Log;

/**
 * Orchestratore notifiche.
 *
 * Responsabilita':
 *  - registry dei sender per CanaleNotifica
 *  - fan-out su piu' canali se la rule lo richiede
 *  - raccolta risultati per canale (true/false)
 *
 * Registrare i sender nel ServiceProvider:
 *
 *   $this->app->singleton(NotificaService::class, function ($app) {
 *       return new NotificaService(
 *           rule: $app->make(CanaleSceltaRule::class),
 *           senders: [
 *               $app->make(\App\Modules\Notifiche\Senders\TelegramSender::class),
 *               $app->make(\App\Modules\Notifiche\Senders\EmailSender::class),
 *               $app->make(\App\Modules\Notifiche\Senders\BrowserPushSender::class),
 *           ],
 *       );
 *   });
 *
 * Esempi d'uso:
 *
 *   // 1) Auto-routing per priorita' (CONSIGLIATO)
 *   $service->notificaOperatore($op, 'Carta sotto soglia', PrioritaNotifica::Alta);
 *
 *   // 2) Manuale, multi-canale per priorita'
 *   $risultati = $service->notifica(
 *       'Ritardo spedizione',
 *       'DDT 12345 in ritardo',
 *       $operatoreOwner,
 *       PrioritaNotifica::Critica,
 *   );
 *   // ['telegram' => true, 'email' => true, 'browser_push' => true]
 *
 *   // 3) Singolo canale esplicito
 *   $service->inviaSuCanale($notifica, CanaleNotifica::Email);
 */
final class NotificaService
{
    /** @var array<string, NotificaSenderInterface> chiave = canale->value */
    private array $registry = [];

    /**
     * @param  iterable<NotificaSenderInterface>  $senders
     */
    public function __construct(
        private readonly CanaleSceltaRule $rule,
        iterable $senders = [],
    ) {
        foreach ($senders as $sender) {
            $this->registry[$sender->canale()->value] = $sender;
        }
    }

    /**
     * Invia su tutti i canali scelti dalla rule per la priorita'/destinatario.
     *
     * @return array<string, bool>  mappa canale->esito (solo canali tentati)
     */
    public function notifica(
        string $titolo,
        string $messaggio,
        mixed $dest,
        PrioritaNotifica $p,
        array $payload = [],
    ): array {
        $operatore = $dest instanceof Operatore ? $dest : null;
        $canali = $this->rule->scegliCanali($p, $operatore);

        $esiti = [];
        foreach ($canali as $canale) {
            $n = new Notifica($titolo, $messaggio, $canale, $p, $dest, $payload);
            $esiti[$canale->value] = $this->inviaSuCanale($n, $canale);
        }

        return $esiti;
    }

    /**
     * Shortcut per operatore: usa la rule, ritorna true se ALMENO un canale ha avuto successo.
     */
    public function notificaOperatore(Operatore $op, string $msg, PrioritaNotifica $p, string $titolo = 'MES'): bool
    {
        $esiti = $this->notifica($titolo, $msg, $op, $p);

        return in_array(true, $esiti, true);
    }

    /**
     * Invia una Notifica gia' costruita su uno specifico canale.
     */
    public function inviaSuCanale(Notifica $n, CanaleNotifica $canale): bool
    {
        $sender = $this->registry[$canale->value] ?? null;

        if ($sender === null) {
            Log::warning('NotificaService: nessun sender registrato', ['canale' => $canale->value]);

            return false;
        }

        if (! $sender->isAvailable()) {
            Log::info('NotificaService: sender non disponibile, skip', ['canale' => $canale->value]);

            return false;
        }

        $payload = $n->canale === $canale ? $n : $n->suCanale($canale);

        return $sender->send($payload);
    }
}
