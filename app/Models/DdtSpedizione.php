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
        'brt_stato',
        'brt_data_consegna',
        'brt_destinatario',
        'brt_colli',
        'brt_cache_at',
    ];

    protected $casts = [
        'data_ddt' => 'date',
        'qta' => 'decimal:2',
        'brt_cache_at' => 'datetime',
    ];

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }
}
