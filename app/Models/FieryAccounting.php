<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieryAccounting extends Model
{
    protected $table = 'fiery_accounting';

    protected $fillable = [
        'job_title', 'commessa', 'data_stampa',
        'fogli', 'copie', 'pagine_colore', 'pagine_bn',
        'formato', 'tipo_formato', 'stato_job',
    ];

    protected $casts = [
        'data_stampa' => 'date',
    ];
}
