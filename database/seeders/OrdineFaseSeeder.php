<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\FasiCatalogo;

class OrdineFasiSeeder extends Seeder
{
    public function run()
    {
        $operatori = Operatore::all();
        $fasiCatalogo = FasiCatalogo::all();

        // Creiamo 10 fasi fittizie
        for ($i = 1; $i <= 10; $i++) {
            OrdineFase::create([
                'ordine_id' => $i,
                'fase' => 'Fase ' . $i,
                'operatore_id' => $operatori->random()->id ?? null,
                'stato' => rand(0,2),
                'data_inizio' => now()->subDays(rand(0,5)),
                'data_fine' => now()->addDays(rand(1,5)),
                'reparto' => 'Stampa',
                'fase_catalogo_id' => $fasiCatalogo->random()->id ?? 1,
                'qta_prod' => rand(0, 100),
                'note' => 'Nota di test ' . $i,
                'timeout' => now()->addMinutes(rand(5,60)),
            ]);
        }
    }
}