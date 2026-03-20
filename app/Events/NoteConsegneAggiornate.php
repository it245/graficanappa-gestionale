<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NoteConsegneAggiornate implements ShouldBroadcast
{
    use SerializesModels;

    public string $contenuto;
    public string $data;
    public string $aggiornato_da;
    public string $updated_at;

    public function __construct(string $contenuto, string $data, string $aggiornato_da)
    {
        $this->contenuto = $contenuto;
        $this->data = $data;
        $this->aggiornato_da = $aggiornato_da;
        $this->updated_at = now()->toISOString();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('note-consegne');
    }

    public function broadcastAs(): string
    {
        return 'aggiornate';
    }
}
