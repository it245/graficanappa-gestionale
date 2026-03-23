<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmInterazione extends Model
{
    protected $table = 'crm_interazioni';

    protected $fillable = [
        'contatto_id', 'tipo', 'note', 'data_interazione',
    ];

    protected $casts = [
        'data_interazione' => 'datetime',
    ];

    public function contatto()
    {
        return $this->belongsTo(CrmContatto::class, 'contatto_id');
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'telefonata' => 'Telefonata',
            'email' => 'Email',
            'incontro' => 'Incontro',
            'messaggio' => 'Messaggio',
            'altro' => 'Altro',
            default => ucfirst($this->tipo),
        };
    }

    public function tipoIcona(): string
    {
        return match ($this->tipo) {
            'telefonata' => '📞',
            'email' => '✉️',
            'incontro' => '🤝',
            'messaggio' => '💬',
            default => '📌',
        };
    }
}
