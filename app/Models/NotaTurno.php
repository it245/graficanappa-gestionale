<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaTurno extends Model
{
    protected $table = 'note_turno';

    protected $fillable = ['operatore_id', 'nota', 'destinazione', 'letta'];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
