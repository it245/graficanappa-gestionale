<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommessaAltroCosto extends Model
{
    protected $table = 'commessa_altri_costi';

    protected $fillable = ['commessa', 'categoria', 'descrizione', 'importo', 'data', 'autore'];

    protected $casts = [
        'data' => 'date',
        'importo' => 'decimal:2',
    ];

    public const CATEGORIE = [
        'cliche'               => 'Cliché',
        'fustella'             => 'Fustella',
        'lavorazione_esterna'  => 'Lavorazione esterna',
        'trasporto'            => 'Trasporto',
        'prove_colore'         => 'Prove colore',
        'materiale_ausiliario' => 'Materiale ausiliario',
        'altro'                => 'Altro',
    ];

    public function categoriaLabel(): string
    {
        return self::CATEGORIE[$this->categoria] ?? $this->categoria;
    }
}
