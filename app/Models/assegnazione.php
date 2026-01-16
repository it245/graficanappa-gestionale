<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assegnazione extends Model
{
    use HasFactory;
    protected $table = 'assegnazioni'; 

    protected $fillable = ['ordine_id','operatore_id'];

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
