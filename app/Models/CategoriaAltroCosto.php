<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaAltroCosto extends Model
{
    protected $table = 'categorie_altri_costi';
    protected $guarded = [];
    protected $casts = ['attiva' => 'boolean'];
}
