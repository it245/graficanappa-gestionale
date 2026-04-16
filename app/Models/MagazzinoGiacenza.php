<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoGiacenza extends Model
{
    protected $table = 'magazzino_giacenze';

    protected $fillable = [
        'articolo_id', 'ubicazione_id', 'quantita', 'lotto',
        'data_ultimo_carico', 'data_ultimo_scarico',
    ];

    protected $casts = [
        'quantita' => 'decimal:2',
        'data_ultimo_carico' => 'date',
        'data_ultimo_scarico' => 'date',
    ];

    public function articolo()
    {
        return $this->belongsTo(MagazzinoArticolo::class, 'articolo_id');
    }

    public function ubicazione()
    {
        return $this->belongsTo(MagazzinoUbicazione::class, 'ubicazione_id')
            ->where('id', '>', 0); // ubicazione_id=0 = nessuna ubicazione (workaround UNIQUE NULL)
    }

    public function etichette()
    {
        return $this->hasMany(MagazzinoEtichetta::class, 'giacenza_id');
    }
}
