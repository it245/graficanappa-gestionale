<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoMovimento extends Model
{
    protected $table = 'magazzino_movimenti';

    protected $fillable = [
        'articolo_id', 'ubicazione_id', 'tipo', 'quantita', 'giacenza_dopo',
        'lotto', 'fornitore', 'commessa', 'fase', 'operatore_id',
        'note', 'foto_bolla', 'ocr_raw',
    ];

    public function articolo()
    {
        return $this->belongsTo(MagazzinoArticolo::class, 'articolo_id');
    }

    public function ubicazione()
    {
        return $this->belongsTo(MagazzinoUbicazione::class, 'ubicazione_id');
    }

    public function operatore()
    {
        return $this->belongsTo(Operatore::class, 'operatore_id');
    }
}
