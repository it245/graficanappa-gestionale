<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// Importiamo il model Operatore
use App\Models\Operatore;

class OperatoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creiamo un operatore di esempio
        Operatore::create([
            'codice_operatore' => 'OP001', // codice univoco operatore
            'nome'   => 'Mario Rossi' // nome dell'operatore
        ]);

        // Possiamo aggiungere altri operatori di test
        Operatore::create([
            'codice_operatore' => 'OP002',
            'nome'   => 'Giulia Bianchi'
        ]);

        Operatore::create([
            'codice_operatore' => 'OP003',
            'nome'   => 'Luca Verdi'
        ]);
    }
}
