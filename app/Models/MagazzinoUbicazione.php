<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoUbicazione extends Model
{
    protected $table = 'magazzino_ubicazioni';

    protected $fillable = ['codice', 'corridoio', 'scaffale', 'piano', 'note'];

    public function giacenze()
    {
        return $this->hasMany(MagazzinoGiacenza::class, 'ubicazione_id');
    }
}
