<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuovoMessaggioChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messaggio;
    public $utente;
    public $canale;
    public $timestamp;

    public function __construct(ChatMessage $chatMessage)
    {
        $this->messaggio = $chatMessage->messaggio;
        $this->utente = $chatMessage->operatore->nome ?? 'Utente';
        $this->canale = $chatMessage->canale;
        $this->timestamp = $chatMessage->created_at->format('H:i');
    }

    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->canale);
    }

    public function broadcastAs(): string
    {
        return 'nuovo-messaggio';
    }
}
