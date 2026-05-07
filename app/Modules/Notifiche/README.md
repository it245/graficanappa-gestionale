# Modulo `Notifiche`

## Scopo

Orchestratore notifiche con fan-out su più canali e routing automatico per
priorità. La regola `CanaleSceltaRule` decide quali canali attivare in base a
`PrioritaNotifica`:

- `Bassa` -> BrowserPush
- `Media` -> BrowserPush + Email
- `Alta`  -> BrowserPush + Email + Telegram
- `Critica` -> tutti (con ack richiesto se possibile)

I `Senders/` sono registrati nel `ServiceProvider` come array iterabile.

## API Pubblica

```
NotificaService::notificaOperatore(Operatore $op, string $msg, PrioritaNotifica $p): array
NotificaService::notifica(string $titolo, string $msg, Operatore $dest, PrioritaNotifica $p): array
NotificaService::inviaSuCanale(Notifica $n, CanaleNotifica $c): bool

interface NotificaSenderInterface
    canale(): CanaleNotifica
    invia(Notifica $n): bool
```

Implementazioni: `TelegramSender`, `EmailSender`, `BrowserPushSender`.
**Templates:** `RitardoSpedizione`, `SottoSogliaCarta`.
**Enums:** `CanaleNotifica::{Telegram,Email,BrowserPush}`,
`PrioritaNotifica::{Bassa,Media,Alta,Critica}`.

## Esempio

```php
use App\Modules\Notifiche\Services\NotificaService;
use App\Modules\Notifiche\Enums\PrioritaNotifica;

$risultati = app(NotificaService::class)->notificaOperatore(
    $owner,
    'Carta 02W.ALASKA sotto soglia (200 fogli)',
    PrioritaNotifica::Alta,
);
// ['telegram' => true, 'email' => true, 'browser_push' => true]
```

## Dipendenze

- `irazasyed/telegram-bot-sdk` (sender Telegram).
- Laravel Mail (sender Email).
- Web Push (`minishlink/web-push`).
- Consumato da: listener cross-modulo (`Magazzino::SottoSogliaEvento`,
  `Spedizione::SpedizioneInRitardo`, `Commessa::CommessaCompletata`).
