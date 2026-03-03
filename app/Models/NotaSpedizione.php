<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaSpedizione extends Model
{
    protected $table = 'note_spedizione';

    protected $fillable = ['data', 'contenuto_am', 'contenuto_pm'];

    protected $casts = ['data' => 'date'];
}
