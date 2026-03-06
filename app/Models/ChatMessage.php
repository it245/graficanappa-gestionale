<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['operatore_id', 'messaggio', 'canale'];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
