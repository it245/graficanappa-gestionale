<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasiCatalogo extends Model
{
    protected $table = 'fasi_catalogo';
    protected $fillable = ['nome', 'reparto_id','pronta_consegna','avviamento','copie_ora'];


 public function reparto(){
    return $this->belongsTo(\App\Models\Reparto::class, 'reparto_id');
 }

 /**
  * Nome abbreviato per la dashboard.
  * STAMPAXL106, STAMPAXL106.1, ... → "STAMPA XL"
  */
 public function getNomeDisplayAttribute(): string
 {
     if (preg_match('/^STAMPAXL106/i', $this->nome)) {
         return 'STAMPA XL';
     }
     return $this->nome;
 }
}