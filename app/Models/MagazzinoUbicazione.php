<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagazzinoUbicazione extends Model
{
    protected $table = 'magazzino_ubicazioni';

    protected $fillable = [
        'codice', 'categoria', 'zona', 'corridoio', 'scaffale', 'piano',
        'capacita_max', 'priorita', 'attiva', 'note',
    ];

    protected $casts = [
        'capacita_max' => 'decimal:2',
        'priorita' => 'integer',
        'attiva' => 'boolean',
    ];

    public function giacenze()
    {
        return $this->hasMany(MagazzinoGiacenza::class, 'ubicazione_id');
    }

    /**
     * Quantità totale attualmente stoccata in questa ubicazione.
     */
    public function quantitaTotale(): float
    {
        return (float) $this->giacenze()->sum('quantita');
    }

    /**
     * Spazio rimanente rispetto a capacita_max. Null se capacità non definita.
     */
    public function spazioRimanente(): ?float
    {
        if ($this->capacita_max === null) return null;
        return max(0, (float) $this->capacita_max - $this->quantitaTotale());
    }

    /**
     * Percentuale occupazione (0-100). Null se capacità non definita.
     */
    public function percentualeOccupazione(): ?float
    {
        if (!$this->capacita_max || $this->capacita_max <= 0) return null;
        return min(100, round(($this->quantitaTotale() / $this->capacita_max) * 100, 1));
    }

    /**
     * Etichetta umana per UI/bot: "Zona A · 02-03-1 (Carta)"
     */
    public function labelCompleta(): string
    {
        $parts = [];
        if ($this->zona) $parts[] = "Zona {$this->zona}";
        $parts[] = $this->codice;
        if ($this->categoria) $parts[] = '(' . ucfirst($this->categoria) . ')';
        return implode(' · ', $parts);
    }
}
