<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoArticolo extends Model
{
    protected $table = 'magazzino_articoli';

    const CATEGORIE = ['carta', 'foil', 'scatoloni', 'inchiostro', 'vernici'];

    protected $fillable = [
        'codice', 'descrizione', 'categoria', 'formato', 'grammatura',
        'spessore', 'um', 'soglia_minima', 'fornitore', 'certificazioni', 'attivo',
    ];

    protected $casts = [
        'attivo' => 'boolean',
        'soglia_minima' => 'decimal:2',
    ];

    public function giacenze()
    {
        return $this->hasMany(MagazzinoGiacenza::class, 'articolo_id');
    }

    public function movimenti()
    {
        return $this->hasMany(MagazzinoMovimento::class, 'articolo_id');
    }

    public function etichette()
    {
        return $this->hasMany(MagazzinoEtichetta::class, 'articolo_id');
    }

    public function giacenzaTotale(): int
    {
        return $this->giacenze()->sum('quantita');
    }

    public function sottoSoglia(): bool
    {
        return $this->soglia_minima > 0 && $this->giacenzaTotale() < $this->soglia_minima;
    }
}
