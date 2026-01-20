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
}