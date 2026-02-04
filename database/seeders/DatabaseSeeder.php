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
    $this->call([
        RepartiSeeder::class,
    ]);
     $this->call([
        FasiCatalogoSeeder::class,
    ]);
      $this->call([
        OperatoreSeeder::class,
    ]);
}
}
