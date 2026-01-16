<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Popola la tabella operatori
        $this->call(OperatoreSeeder::class);

        // Popola la tabella ordini e ordini_operatori
        $this->call(OrdineSeeder::class);

        // Popola le fasi per ogni ordine
        $this->call(OrdineFaseSeeder::class);

        // Popola eventuali pause degli operatori
        $this->call(PausaOperatoreSeeder::class);
    }
}
