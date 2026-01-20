<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operatore extends Model
{
    use HasFactory;
     
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
}
