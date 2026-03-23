<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmContatto extends Model
{
    protected $table = 'crm_contatti';

    protected $fillable = [
        'nome', 'cognome', 'azienda', 'ruolo', 'email', 'telefono',
        'categoria', 'priorita', 'frequenza_followup_giorni',
        'ultimo_contatto', 'prossimo_followup', 'note',
    ];

    protected $casts = [
        'ultimo_contatto' => 'date',
        'prossimo_followup' => 'date',
    ];

    public function interazioni()
    {
        return $this->hasMany(CrmInterazione::class, 'contatto_id')->orderByDesc('data_interazione');
    }

    public function nomeCompleto(): string
    {
        return trim($this->nome . ' ' . ($this->cognome ?? ''));
    }

    public function isFollowupScaduto(): bool
    {
        return $this->prossimo_followup && $this->prossimo_followup->isPast();
    }

    public function isFollowupImminente(): bool
    {
        return $this->prossimo_followup
            && !$this->prossimo_followup->isPast()
            && $this->prossimo_followup->diffInDays(now()) <= 3;
    }

    public function ricalcolaProssimoFollowup(): void
    {
        if ($this->ultimo_contatto) {
            $this->prossimo_followup = $this->ultimo_contatto->copy()->addDays($this->frequenza_followup_giorni);
        }
    }
}
