<?php

namespace App\Models;

use App\Modules\Fustelle\Enums\StatoFustella;
use App\Modules\Fustelle\Enums\TipoFustella;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Anagrafica fustelle/cliché (tabella `fustelle`).
 *
 * NB: distinta dalla `cliche_anagrafica` legacy, che resta in uso per le
 * importazioni Excel del fustellificio. Questo modello è il punto centrale
 * per il modulo App\Modules\Fustelle.
 *
 * @property int                 $id
 * @property string              $codice           formato F-NNNNN-X
 * @property TipoFustella        $tipo
 * @property StatoFustella       $stato
 * @property int|null            $dimensione_mm_x
 * @property int|null            $dimensione_mm_y
 * @property float|null          $spessore_mm
 * @property string|null         $posizione_magazzino
 * @property string|null         $note
 */
class Fustella extends Model
{
    use HasFactory;

    protected $table = 'fustelle';

    protected $fillable = [
        'codice',
        'tipo',
        'stato',
        'dimensione_mm_x',
        'dimensione_mm_y',
        'spessore_mm',
        'posizione_magazzino',
        'note',
    ];

    protected $casts = [
        'tipo' => TipoFustella::class,
        'stato' => StatoFustella::class,
        'dimensione_mm_x' => 'integer',
        'dimensione_mm_y' => 'integer',
        'spessore_mm' => 'float',
    ];
}
