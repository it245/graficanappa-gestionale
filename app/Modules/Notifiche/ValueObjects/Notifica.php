<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\ValueObjects;

use App\Models\Operatore;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;

/**
 * DTO immutabile che rappresenta una singola notifica pronta da inviare.
 *
 * Il destinatario e' tipato `mixed` perche' dipende dal canale:
 *  - Telegram     -> int|string chat_id  (oppure Operatore con chat_id risolvibile)
 *  - Email        -> string indirizzo email (oppure Operatore con email)
 *  - BrowserPush  -> Operatore (per filtrare per ruolo/reparto)
 *  - Toast/Beep   -> Operatore (id) o null per broadcast dashboard owner
 *
 * Esempio:
 *   $n = new Notifica(
 *       'Carta sotto soglia',
 *       'Solo 2 bancali rimasti',
 *       CanaleNotifica::Telegram,
 *       PrioritaNotifica::Alta,
 *       destinatario: 123456789,
 *       payload: ['codice_carta' => 'PAT250']
 *   );
 */
final readonly class Notifica
{
    /**
     * @param  array<string, mixed>  $payload  contesto applicativo (codici, id, link)
     */
    public function __construct(
        public string $titolo,
        public string $messaggio,
        public CanaleNotifica $canale,
        public PrioritaNotifica $priorita,
        public mixed $destinatario,
        public array $payload = [],
    ) {
    }

    /**
     * Helper: se destinatario e' un Operatore, ritorna l'istanza, altrimenti null.
     */
    public function operatore(): ?Operatore
    {
        return $this->destinatario instanceof Operatore ? $this->destinatario : null;
    }

    /** Clone con canale diverso (utile per fan-out multi-canale). */
    public function suCanale(CanaleNotifica $canale): self
    {
        return new self(
            $this->titolo,
            $this->messaggio,
            $canale,
            $this->priorita,
            $this->destinatario,
            $this->payload,
        );
    }
}
