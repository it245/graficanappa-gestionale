<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiltroPreferitoCosti extends Model
{
    protected $table = 'filtri_preferiti_costi';
    protected $guarded = [];
    protected $casts = ['filtri' => 'array'];
}
