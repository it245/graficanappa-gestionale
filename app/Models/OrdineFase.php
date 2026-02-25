<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FasiCatalogo;

class OrdineFase extends Model
{
    use HasFactory;

    protected $table = 'ordine_fasi';

    protected $fillable = [
        'ordine_id',
        'fase',
        'operatore_id',
        'stato',
        'data_inizio',
        'data_fine',
        'reparto',
        'fase_catalogo_id',
        'qta_prod',
        'scarti',
        'qta_fase',
        'um',
        'note',
        'priorita',
        'ore',
        'tipo_consegna',
        'qta_consegnata',
    ];

    /* ===================== RELAZIONI ===================== */

    public function ordine()
    {
        return $this->belongsTo(Ordine::class);
    }

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }
    public function fase()
{
    return $this->belongsTo(Fase::class);
}

    public function faseCatalogo()
{
    return $this->belongsTo(FasiCatalogo::class, 'fase_catalogo_id');
}

    public function operatori()
    {
        return $this->belongsToMany(Operatore::class, 'fase_operatore', 'fase_id', 'operatore_id')
            ->withPivot('data_inizio','data_fine','secondi_pausa')
            ->withTimestamps();
    }

    public function reparto()
{
    return $this->hasOneThrough(
        Reparto::class,         // modello finale
        FasiCatalogo::class,    // modello intermediario
        'id',                   // chiave primaria di fasi_catalogo
        'id',                   // chiave primaria di reparto
        'fase_catalogo_id',     // chiave locale in ordine_fasi
        'reparto_id'            // chiave esterna in fasi_catalogo
    );
}
    /* ===================== LOGICA ===================== */

    

    public function avvia(Operatore $operatore)
{
    if ($this->stato === 1) {
        throw new \Exception('Fase già in lavorazione');
    }

    $this->update([
        'operatore_id' => $operatore->id,
        'stato' => 1,
        'data_inizio' => now(),
    ]);
}

public function aggiungiProduzione(int $quantita)
{
    if ($this->stato !== 1) {
        throw new \Exception('La fase non è in lavorazione');
    }

    $totale = $this->ordine->fasi()->sum('qta_prod');
    $richiesta = $this->ordine->qta_richiesta;

    if ($totale + $quantita > $richiesta) {
        throw new \Exception('Superata quantità ordine');
    }

    $this->increment('qta_prod', $quantita);

    $this->ordine->update([
        'qta_prodotta' => $this->ordine->fasi()->sum('qta_prod')
    ]);
}

public function termina()
{
    if ($this->stato !== 1) {
        throw new \Exception('La fase non è in lavorazione');
    }

    $this->update([
        'stato' => 2,
        'data_fine' => now(),
    ]);
}
}

