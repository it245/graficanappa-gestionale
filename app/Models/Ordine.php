<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordine extends Model
{
    use HasFactory;
    protected $table = 'ordini';

    protected $fillable = [
        'commessa', 'cliente_nome', 'cod_art', 'descrizione',
        'qta_richiesta', 'qta_prodotta', 'um', 'stato', 'priorita',
        'data_registrazione', 'data_prevista_consegna', 'pronto_consegna', 'note',
        'ore_lavorate', 'timeout_macchina','cod_carta','carta','qta_carta','UM_carta'
    ];

    public function articoli()
    {
        return $this->hasMany(Articolo::class);
    }

   /* public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
*/
    public function fasi()
    {
        return $this->hasMany(OrdineFase::class);
    }
    
  
    public function operatori()
{
    return $this->hasManyThrough(Operatore::class, OrdineFase::class, 'ordine_id', 'id', 'id', 'operatore_id');
}


    public function assegnazioni()
    {
        return $this->hasMany(Assegnazione::class);
    }

    public function pause()
    {
        return $this->hasMany(PausaOperatore::class);
    }
    public function reparto(){
        return $this->belongsTo(\App\Models\Reparto::class);
    }
}


