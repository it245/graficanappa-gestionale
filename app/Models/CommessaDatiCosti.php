<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommessaDatiCosti extends Model
{
    protected $table = 'commessa_dati_costi';

    protected $fillable = [
        'commessa', 'fogli_utilizzati', 'tiri_cm_foil',
        'inchiostro_g', 'scarti_fogli', 'autore',
    ];

    protected $casts = [
        'fogli_utilizzati' => 'integer',
        'tiri_cm_foil'     => 'decimal:2',
        'inchiostro_g'     => 'decimal:2',
        'scarti_fogli'     => 'integer',
    ];
}
