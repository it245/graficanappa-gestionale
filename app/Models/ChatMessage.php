<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['operatore_id', 'messaggio', 'canale', 'hidden_for'];

    protected $casts = [
        'hidden_for' => 'array',
    ];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    public function isHiddenFor(?int $operatoreId): bool
    {
        if (!$operatoreId) return false;
        $hidden = $this->hidden_for ?? [];
        return in_array($operatoreId, $hidden);
    }
}
