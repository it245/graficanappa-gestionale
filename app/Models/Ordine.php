<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordine extends Model
{
    use HasFactory;
    protected $table = 'ordini';

    protected $fillable = [
        'commessa', 'cliente_nome', 'cod_art', 'descrizione',
        'qta_richiesta', 'qta_prodotta', 'um', 'stato', 'priorita',
        'data_registrazione', 'data_prevista_consegna', 'pronto_consegna', 'note',
        'ore_lavorate', 'timeout_macchina','cod_carta','carta','qta_carta','UM_carta',
        'supp_base_cm', 'supp_altezza_cm', 'resa', 'tot_supporti',
        'valore_ordine', 'costo_materiali',
        'note_prestampa', 'responsabile', 'commento_produzione', 'note_fasi_successive', 'ordine_cliente',
        'ddt_vendita_id', 'numero_ddt_vendita', 'vettore_ddt', 'qta_ddt_vendita',
        'cliche_numero', 'cliche_match_type', 'cliche_matched_at',
    ];

    protected $casts = [
        'cliche_matched_at' => 'datetime',
    ];

    public function cliche()
    {
        return $this->belongsTo(\App\Models\ClicheAnagrafica::class, 'cliche_numero', 'numero');
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class);
    }

   /* public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
*/
    public function fasi()
    {
        return $this->hasMany(OrdineFase::class);
    }
    
  
    public function operatori()
{
    return $this->hasManyThrough(Operatore::class, OrdineFase::class, 'ordine_id', 'id', 'id', 'operatore_id');
}


    public function assegnazioni()
    {
        return $this->hasMany(Assegnazione::class);
    }

    public function pause()
    {
        return $this->hasMany(PausaOperatore::class);
    }
    public function reparto(){
        return $this->belongsTo(\App\Models\Reparto::class);
    }

    /**
     * Determina il percorso produttivo dell'ordine (caldo/rilievi)
     * e restituisce la classe CSS corrispondente.
     *
     * Verde:   nè caldo nè rilievi
     * Giallo:  rilievi ma no caldo
     * Arancio: caldo ma no rilievi
     * Rosa:    percorso completo (caldo + rilievi)
     */
    public function getPercorsoClass(): string
    {
        // Guarda tutte le fasi della stessa commessa (non solo di questo ordine)
        $tutteFasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $this->commessa))
            ->with('faseCatalogo')
            ->get();

        $haCaldo = false;
        $haRilievi = false;

        foreach ($tutteFasi as $fase) {
            $nome = strtoupper($fase->faseCatalogo->nome ?? $fase->fase ?? '');
            if (str_contains($nome, 'STAMPACALDO')) $haCaldo = true;
            if ($nome === 'FUSTBOBSTRILIEVI')       $haRilievi = true;
            if ($haCaldo && $haRilievi) break;
        }

        if ($haCaldo && $haRilievi) return 'percorso-completo';
        if ($haCaldo)               return 'percorso-caldo';
        if ($haRilievi)             return 'percorso-rilievi';
        return 'percorso-base';
    }
}


