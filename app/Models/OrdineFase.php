<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdineFase extends Model
{
    use HasFactory;

    protected $table = 'ordine_fasi';

    protected $fillable = ['ordine_id','fase','operatore_id','stato','qta_prod','data_inizio','data_fine'];

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
}
