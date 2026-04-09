<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoEtichetta extends Model
{
    protected $table = 'magazzino_etichette';

    protected $fillable = [
        'qr_code', 'articolo_id', 'ubicazione_id', 'giacenza_id',
        'lotto', 'quantita_iniziale', 'attiva',
    ];

    protected $casts = [
        'attiva' => 'boolean',
    ];

    public function articolo()
    {
        return $this->belongsTo(MagazzinoArticolo::class, 'articolo_id');
    }

    public function ubicazione()
    {
        return $this->belongsTo(MagazzinoUbicazione::class, 'ubicazione_id');
    }

    public function giacenza()
    {
        return $this->belongsTo(MagazzinoGiacenza::class, 'giacenza_id');
    }
}
