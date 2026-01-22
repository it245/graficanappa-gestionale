<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Operatore extends Authenticatable
{
    use Notifiable;
     
    protected $table = 'operatori'; 

    protected $fillable = ['nome','cognome','codice_operatore','ruolo','attivo','reparto'];
     public function ordini()
{
    return $this->belongsToMany(
        Ordine::class,
        'assegnazioni'
    );
}


    public function assegnazioni()
    {
        return $this->hasMany(Assegnazione::class);
    }

    public function fasi()
    {
        return $this->hasMany(OrdineFase::class);
    }

    public function pause()
    {
        return $this->hasMany(PausaOperatore::class);
    }
    public function reparto()
{
    return $this->belongsTo(Reparto::class);
}

public function getRepartiArrayAttribute()
{
    if (!$this->reparto) {
        return [];
    }
    return array_filter(explode(',', $this->reparto));
}

}