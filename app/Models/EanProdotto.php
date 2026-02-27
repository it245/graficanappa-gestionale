<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EanProdotto extends Model
{
    protected $table = 'ean_prodotti';

    protected $fillable = ['articolo', 'codice_ean'];
}
