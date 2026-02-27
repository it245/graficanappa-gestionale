<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reparto;

class RepartiSeeder extends Seeder
{
    public function run(): void
    {
        $reparti = [
            ['id' => 1, 'nome' => 'spedizione'],
            ['id' => 2, 'nome' => 'produzione'],
            ['id' => 3, 'nome' => 'magazzino'],
            ['id' => 4, 'nome' => 'digitale'],
            ['id' => 5, 'nome' => 'fustella'],
            ['id' => 6, 'nome' => 'legatoria'],
            ['id' => 7, 'nome' => 'piegaincolla'],
            ['id' => 8, 'nome' => 'plastificazione'],
            ['id' => 9, 'nome' => 'prestampa'],
            ['id' => 10, 'nome' => 'stampa a caldo'],
            ['id' => 11, 'nome' => 'stampa offset'],
            ['id'=> 12, 'nome'=>'esterno'],
            ['id'=> 13, 'nome'=>'rilievo'],
        ];

        foreach ($reparti as $reparto) {
            Reparto::updateOrCreate(
                ['id' => $reparto['id']],
                ['nome' => $reparto['nome']]
            );
        }
    }
}