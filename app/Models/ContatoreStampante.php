<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContatoreStampante extends Model
{
    protected $table = 'contatori_stampante';

    protected $fillable = [
        'stampante',
        'ip',
        'totale_1',
        'nero_grande',
        'nero_piccolo',
        'colore_grande',
        'colore_piccolo',
        'scansioni',
        'foglio_lungo',
        'rilevato_at',
    ];

    protected $casts = [
        'rilevato_at' => 'datetime',
    ];
}
