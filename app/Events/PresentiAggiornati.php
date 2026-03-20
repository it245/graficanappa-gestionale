<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PresentiAggiornati implements ShouldBroadcast
{
    use SerializesModels;

    public int $totale_presenti;
    public int $totale_usciti;
    public string $ultimo_sync;

    public function __construct(int $totale_presenti, int $totale_usciti, string $ultimo_sync)
    {
        $this->totale_presenti = $totale_presenti;
        $this->totale_usciti = $totale_usciti;
        $this->ultimo_sync = $ultimo_sync;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('presenze');
    }

    public function broadcastAs(): string
    {
        return 'aggiornati';
    }
}
