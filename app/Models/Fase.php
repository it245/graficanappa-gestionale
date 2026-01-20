<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fase extends Model
{
    use HasFactory;
    protected $table = 'fasi';

    protected $fillable = ['nome', 'reparto_id','gruppo'];

    public function reparto()
    {
        return $this->belongsTo(Reparto::class);
    }

    public function ordineFasi()
    {
        return $this->hasMany(OrdineFase::class);
    }
}

