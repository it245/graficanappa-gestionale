<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalisiCustomCommessa extends Model
{
    protected $table = 'analisi_custom_commesse';
    protected $fillable = ['analisi_id', 'commessa', 'override_voci', 'etichetta', 'ordine'];
    protected $casts = [
        'override_voci' => 'array',
    ];

    public function analisi(): BelongsTo
    {
        return $this->belongsTo(AnalisiCustom::class, 'analisi_id');
    }
}
