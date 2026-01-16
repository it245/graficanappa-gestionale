<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PausaOperatore extends Model
{
    use HasFactory;
    protected $table = 'pausa_operatores';


    protected $fillable = ['operatore_id','ordine_id','fase','motivo','data_ora'];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }
}
