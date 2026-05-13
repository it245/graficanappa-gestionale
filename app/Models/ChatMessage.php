<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // commentato per software house

class ChatMessage extends Model
{
    // use SoftDeletes; // commentato per software house

    protected $fillable = ['operatore_id', 'messaggio', 'canale'];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    // Letture / hidden_for / isHiddenFor() — commentati per software house
}
