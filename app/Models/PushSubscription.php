<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'operatore_id',
        'endpoint',
        'p256dh_key',
        'auth_token',
        'ruolo',
    ];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
