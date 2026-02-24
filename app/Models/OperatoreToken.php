<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatoreToken extends Model
{
    protected $table = 'operatore_tokens';

    protected $fillable = ['operatore_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    public function scopeValido($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
