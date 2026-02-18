<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinectAttivita extends Model
{
    protected $table = 'prinect_attivita';

    protected $fillable = [
        'device_id',
        'device_name',
        'activity_id',
        'activity_name',
        'time_type_name',
        'time_type_group',
        'prinect_job_id',
        'prinect_job_name',
        'commessa_gestionale',
        'workstep_name',
        'good_cycles',
        'waste_cycles',
        'start_time',
        'end_time',
        'operatore_prinect',
        'cost_center',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
