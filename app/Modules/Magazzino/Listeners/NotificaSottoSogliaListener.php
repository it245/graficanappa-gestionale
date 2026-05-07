<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Listeners;

use App\Modules\Magazzino\Events\SottoSogliaEvento;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Services\NotificaService;
use App\Modules\Notifiche\Templates\SottoSogliaCarta;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Invia notifica Telegram alla chat magazzino quando una carta scende
 * sotto soglia minima. Listener sincrono (singolo canale, basso costo).
 *
 * Destinatario: env TELEGRAM_MAGAZZINO_CHAT_ID (chat group magazzino).
 * In assenza di env il listener logga warning e non interrompe il movimento.
 */
final class NotificaSottoSogliaListener
{
    public function __construct(
        private readonly NotificaService $notifiche,
    ) {
    }

    public function handle(SottoSogliaEvento $event): void
    {
        $chatId = env('TELEGRAM_MAGAZZINO_CHAT_ID');
        if (empty($chatId)) {
            Log::warning('NotificaSottoSogliaListener: TELEGRAM_MAGAZZINO_CHAT_ID non configurato', [
                'articolo' => $event->articolo->codice,
                'giacenza' => $event->giacenzaCorrente,
                'soglia' => $event->sogliaMinima,
            ]);
            return;
        }

        try {
            $notifica = SottoSogliaCarta::genera([
                'codice' => $event->articolo->codice,
                'descrizione' => $event->articolo->descrizione,
                'giacenza' => $event->giacenzaCorrente,
                'soglia' => $event->sogliaMinima,
                'destinatario' => $chatId,
                'canale' => CanaleNotifica::Telegram,
            ]);

            $esito = $this->notifiche->inviaSuCanale($notifica, CanaleNotifica::Telegram);

            Log::info('NotificaSottoSogliaListener: notifica inviata', [
                'articolo' => $event->articolo->codice,
                'giacenza' => $event->giacenzaCorrente,
                'soglia' => $event->sogliaMinima,
                'esito' => $esito,
            ]);
        } catch (Throwable $e) {
            // Non rilanciamo: la notifica fallita non deve rollbackare il movimento.
            Log::error('NotificaSottoSogliaListener: errore invio', [
                'articolo' => $event->articolo->codice,
                'errore' => $e->getMessage(),
            ]);
        }
    }
}
