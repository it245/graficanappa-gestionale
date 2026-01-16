<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ordine; // <- aggiungi questa riga per importare il model

class OrdineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ordine::create([
    'priorita' => 1,
    'commessa' => '0066016-26',
    'cliente_nome' => 'ITALIANA CONFETTI SRL',
    'cod_art' => 'FUSTBOBSTRILIEVI',
    'descrizione' => 'Astuccio Lamponì bianco FS2236',
    'qta_richiesta' => 2000,
    'data_prevista_consegna' => '2026-01-12',
    'pronto_consegna' => 0,
    'note' => '',
    'data_registrazione' => now()
]);

    }
}
