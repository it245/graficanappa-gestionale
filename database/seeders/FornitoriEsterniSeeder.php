<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FornitoriEsterniSeeder extends Seeder
{
    public function run(): void
    {
        // Aggiunge fornitori esterni catalogo (non duplicare se già esistenti)
        $fornitori = [
            ['slug' => 'soluzioni_imballaggi_shopper', 'nome' => 'Soluzioni Imballaggi — Shopper', 'tipo' => 'shopper',           'categoria' => 'fornitore_esterno'],
            ['slug' => 'soluzioni_imballaggi_acc',     'nome' => 'Soluzioni Imballaggi — Acc+Fust+Inc','tipo' => 'accoppiamento',  'categoria' => 'fornitore_esterno'],
            ['slug' => 'neoprint',                      'nome' => 'Neoprint',                            'tipo' => 'stampa_esterna', 'categoria' => 'fornitore_esterno'],
            ['slug' => 'kresia_uv1',                    'nome' => 'Kresia — Plastificazione UV1',        'tipo' => 'plastificazione','categoria' => 'fornitore_esterno'],
        ];
        foreach ($fornitori as $f) {
            if (!DB::table('macchine_costi')->where('slug', $f['slug'])->exists()) {
                DB::table('macchine_costi')->insert(array_merge($f, [
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }
        }
        $this->command->info('FornitoriEsterniSeeder: ' . count($fornitori) . ' fornitori esterni in catalogo.');
    }
}
