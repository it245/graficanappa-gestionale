<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdineFase;

class OrdineFaseSeeder extends Seeder
{
    public function run(): void
    {
        OrdineFase::create([
            'ordine_id' => 1,
            'fase' => 'STAMPA',
            'stato' => 0,
            'qta_prod' => 0,
            'data_inizio' => null,
            'data_fine' => null
        ]);

        OrdineFase::create([
            'ordine_id' => 1,
            'fase' => 'PIEGAINCOLLA',
            'stato' => 0,
            'qta_prod' => 0,
            'data_inizio' => null,
            'data_fine' => null
        ]);

        OrdineFase::create([
            'ordine_id' => 1,
            'fase' => 'TAGLIACARTE',
            'stato' => 0,
            'qta_prod' => 0,
            'data_inizio' => null,
            'data_fine' => null
        ]);
    }
}
