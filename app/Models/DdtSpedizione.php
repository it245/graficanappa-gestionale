<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DdtSpedizione extends Model
{
    protected $table = 'ddt_spedizioni';

    protected $fillable = [
        'onda_id_doc',
        'numero_ddt',
        'data_ddt',
        'vettore',
        'cliente_nome',
        'commessa',
        'ordine_id',
        'qta',
    ];

    protected $casts = [
        'data_ddt' => 'date',
        'qta' => 'decimal:2',
    ];

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }
}
