<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatoreToken extends Model
{
    protected $fillable = [
        'operatore_id',
        'token',
    ];
    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
