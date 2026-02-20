<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostoReparto extends Model
{
    protected $table = 'costi_reparto';

    protected $fillable = ['reparto_id', 'costo_orario', 'valido_dal', 'valido_al', 'note'];

    protected $casts = [
        'valido_dal' => 'date',
        'valido_al' => 'date',
    ];

    public function reparto()
    {
        return $this->belongsTo(Reparto::class);
    }

    /**
     * Tariffa oraria valida per un reparto a una certa data.
     * Ritorna 0 se nessuna tariffa configurata.
     */
    public static function tariffaAllaData(int $repartoId, string $data): float
    {
        $riga = self::where('reparto_id', $repartoId)
            ->where('valido_dal', '<=', $data)
            ->where(function ($q) use ($data) {
                $q->whereNull('valido_al')->orWhere('valido_al', '>=', $data);
            })
            ->orderByDesc('valido_dal')
            ->first();

        return $riga ? (float) $riga->costo_orario : 0.0;
    }
}
