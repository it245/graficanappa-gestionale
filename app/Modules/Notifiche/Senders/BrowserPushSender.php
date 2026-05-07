<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Senders;

use App\Models\Operatore;
use App\Modules\Notifiche\Contracts\NotificaSenderInterface;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Sender Browser Push: persiste su `browser_notifications`, polling JS lato dashboard
 * (owner / operatore) la legge ogni ~1s e mostra toast + beep.
 *
 * Schema atteso (creare migration se non esiste):
 *   id, operatore_id (nullable, broadcast se null), titolo, messaggio,
 *   priorita, payload (json), letta_at, created_at, updated_at
 *
 * Destinatario:
 *  - Operatore -> persiste con operatore_id
 *  - null      -> broadcast (operatore_id NULL)
 *
 * Esempio:
 *   $sender->send(new Notifica('Nuova nota consegna', '...',
 *       CanaleNotifica::BrowserPush, PrioritaNotifica::Alta,
 *       destinatario: $operatore));
 */
final class BrowserPushSender implements NotificaSenderInterface
{
    private const TABLE = 'browser_notifications';

    public function canale(): CanaleNotifica
    {
        return CanaleNotifica::BrowserPush;
    }

    public function isAvailable(): bool
    {
        try {
            return Schema::hasTable(self::TABLE);
        } catch (Throwable) {
            return false;
        }
    }

    public function send(Notifica $n): bool
    {
        if (! $this->isAvailable()) {
            Log::warning('BrowserPushSender: tabella '.self::TABLE.' assente, notifica scartata', [
                'titolo' => $n->titolo,
            ]);

            return false;
        }

        try {
            DB::table(self::TABLE)->insert([
                'operatore_id' => $this->resolveOperatoreId($n->destinatario),
                'titolo' => mb_substr($n->titolo, 0, 255),
                'messaggio' => $n->messaggio,
                'priorita' => $n->priorita->value,
                'payload' => json_encode($n->payload, JSON_UNESCAPED_UNICODE) ?: '{}',
                'letta_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('BrowserPushSender: insert fallito', [
                'titolo' => $n->titolo,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveOperatoreId(mixed $destinatario): ?int
    {
        if ($destinatario instanceof Operatore) {
            return (int) $destinatario->id;
        }

        if (is_int($destinatario)) {
            return $destinatario;
        }

        return null; // broadcast
    }
}
