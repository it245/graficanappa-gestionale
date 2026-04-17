<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClicheAnagrafica extends Model
{
    protected $table = 'cliche_anagrafica';

    protected $fillable = ['numero', 'descrizione_raw', 'qta', 'scatola', 'note'];

    /**
     * Etichetta display: C{numero}-S{scatola}
     */
    public function label(): string
    {
        $out = 'C' . $this->numero;
        if ($this->scatola !== null) $out .= '-S' . $this->scatola;
        return $out;
    }
}
