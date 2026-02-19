<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Operatore extends Authenticatable
{
    use Notifiable;
     
    protected $table = 'operatori'; 

    protected $fillable = ['nome','cognome','codice_operatore','ruolo','attivo','reparto','reparto_id','password'];

    protected $hidden = ['password'];
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
        return $this->belongsToMany(OrdineFase::class, 'fase_operatore', 'operatore_id', 'fase_id')
                    ->withPivot('data_inizio')
                    ->withTimeStamps();
    }

    public function pause()
    {
        return $this->hasMany(PausaOperatore::class);
    }
    public function reparto()
{
    return $this->belongsTo(Reparto::class,'reparto_id');
}

public function getRepartiArrayAttribute()
{
    if (!$this->reparto) {
        return [];
    }
    return array_filter(explode(',', $this->reparto));
}

public function reparti(){
    return $this->belongsToMany(Reparto::class,'operatore_reparto','operatore_id','reparto_id')->withTimeStamps();
}

}