<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NotificaEsterna implements ShouldBroadcast
{
    use SerializesModels;

    public string $commessa;
    public string $fase;
    public string $fornitore;
    public string $tipo; // 'inviata' o 'rientrata'
    public string $timestamp;

    public function __construct(string $commessa, string $fase, string $fornitore, string $tipo = 'inviata')
    {
        $this->commessa = $commessa;
        $this->fase = $fase;
        $this->fornitore = $fornitore;
        $this->tipo = $tipo;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('notifiche-esterne');
    }

    public function broadcastAs(): string
    {
        return 'nuova';
    }
}
