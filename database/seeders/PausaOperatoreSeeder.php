<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PausaOperatore;
use Carbon\Carbon;

class PausaOperatoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PausaOperatore::create([
            'operatore_id' => 1,              // collega all'operatore
            'ordine_id' => 1,                 // collega all'ordine
            'fase' => 'STAMPA',               // fase in cui è in pausa
            'motivo' => 'Pausa pranzo',       // motivo pausa
            'data_ora' => Carbon::now()       // momento della pausa
        ]);
    }
}
