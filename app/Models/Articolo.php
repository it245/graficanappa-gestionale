<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articolo extends Model
{
    use HasFactory;
    protected $table = 'articoli'; 

    protected $fillable = [
        'ordine_id','cod_art','descrizione','qta_richiesta','qta_prodotta','um',
        'cod_carta','carta','qta_carta'
    ];

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }
}

