<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Creo fasi mancanti nel catalogo
        $nuoveFasi = [
            ['nome' => 'STAMPACALDOJOH0,2', 'reparto_id' => 10], // stampa a caldo
            ['nome' => 'PMDUPLO40AUTO',     'reparto_id' => 6],  // legatoria
            ['nome' => 'FASCETTATURA',      'reparto_id' => 6],  // legatoria
            ['nome' => 'EXTACCOPPIATURA.FOG.33.48', 'reparto_id' => 12], // esterno
            ['nome' => 'EXTALLEST.SHOPPER024',      'reparto_id' => 12], // esterno
        ];

        foreach ($nuoveFasi as $fase) {
            // Inserisci solo se non esiste già
            if (!DB::table('fasi_catalogo')->where('nome', $fase['nome'])->exists()) {
                DB::table('fasi_catalogo')->insert([
                    'nome' => $fase['nome'],
                    'reparto_id' => $fase['reparto_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('fasi_catalogo')->whereIn('nome', [
            'STAMPACALDOJOH0,2', 'PMDUPLO40AUTO', 'FASCETTATURA',
            'EXTACCOPPIATURA.FOG.33.48', 'EXTALLEST.SHOPPER024',
        ])->delete();
    }
};
