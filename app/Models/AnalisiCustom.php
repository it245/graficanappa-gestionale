<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalisiCustom extends Model
{
    protected $table = 'analisi_custom';
    protected $fillable = ['nome', 'descrizione', 'autore', 'filtri', 'opzioni_view', 'ultimo_accesso'];
    protected $casts = [
        'filtri'          => 'array',
        'opzioni_view'    => 'array',
        'ultimo_accesso'  => 'datetime',
    ];

    public function commesse(): HasMany
    {
        return $this->hasMany(AnalisiCustomCommessa::class, 'analisi_id');
    }
}
