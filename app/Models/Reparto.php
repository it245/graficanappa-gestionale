<?php

// app/Models/Reparto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reparto extends Model
{
    use HasFactory;
  protected $table='reparti';
    protected $fillable = ['nome','codice'];

    public function operatori()
    {
        return $this->hasMany(Operatore::class,'operatore_reparto','reparto_id','operatore_id')->withTimeStamps();
    }

    public function fasi()
    {
        return $this->hasMany(Fase::class);
    }

    public function costiOrari()
    {
        return $this->hasMany(CostoReparto::class)->orderByDesc('valido_dal');
    }
}

