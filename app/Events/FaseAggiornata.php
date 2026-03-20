<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class FaseAggiornata implements ShouldBroadcast
{
    use SerializesModels;

    public int $fase_id;
    public string $commessa;
    public string $campo;
    public mixed $valore;
    public string $timestamp;

    public function __construct(int $fase_id, string $commessa, string $campo, mixed $valore)
    {
        $this->fase_id = $fase_id;
        $this->commessa = $commessa;
        $this->campo = $campo;
        $this->valore = $valore;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('produzione');
    }

    public function broadcastAs(): string
    {
        return 'fase-aggiornata';
    }
}
