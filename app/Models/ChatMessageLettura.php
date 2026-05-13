<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessageLettura extends Model
{
    protected $table = 'chat_message_letture';

    protected $fillable = ['chat_message_id', 'operatore_id', 'letto_at'];

    protected $casts = [
        'letto_at' => 'datetime',
    ];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }
}
