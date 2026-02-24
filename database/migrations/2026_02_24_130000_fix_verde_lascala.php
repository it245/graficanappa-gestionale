<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rimuovere legatoria (6) da Francesco Verde (ID 4)
        //    (era stata aggiunta per errore nella migration precedente)
        DB::table('operatore_reparto')
            ->where('operatore_id', 4)
            ->where('reparto_id', 6)
            ->delete();

        // 2. Creare reparto dedicato per le fasi di Verde
        $repartoVerde = DB::table('reparti')->insertGetId([
            'nome'       => 'finitura digitale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Spostare le 4 fasi nel nuovo reparto
        //    CORDONATURAPETRATTO (40), DEKIA-Difficile (41), DEKIA-semplice (112), PIEGA2ANTECORDONE (48)
        DB::table('fasi_catalogo')
            ->whereIn('id', [40, 41, 112, 48])
            ->update(['reparto_id' => $repartoVerde]);

        // 4. Assegnare Francesco Verde (ID 4) al nuovo reparto
        DB::table('operatore_reparto')->insert([
            'operatore_id' => 4,
            'reparto_id'   => $repartoVerde,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // 5. Aggiungere fustella cilindrica (16) a Gennaro La Scala
        $gennaro = DB::table('operatori')
            ->where('nome', 'Gennaro')
            ->where('cognome', 'La Scala')
            ->first();

        if ($gennaro) {
            if (!DB::table('operatore_reparto')->where('operatore_id', $gennaro->id)->where('reparto_id', 16)->exists()) {
                DB::table('operatore_reparto')->insert([
                    'operatore_id' => $gennaro->id,
                    'reparto_id'   => 16, // fustella cilindrica
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Ripristina le 4 fasi a legatoria (6)
        DB::table('fasi_catalogo')
            ->whereIn('id', [40, 41, 112, 48])
            ->update(['reparto_id' => 6]);

        // Rimuovi reparto e assegnazione Verde
        $repartoVerde = DB::table('reparti')->where('nome', 'finitura digitale')->first();
        if ($repartoVerde) {
            DB::table('operatore_reparto')->where('reparto_id', $repartoVerde->id)->delete();
            DB::table('reparti')->where('id', $repartoVerde->id)->delete();
        }

        // Ri-aggiungi legatoria a Verde
        if (!DB::table('operatore_reparto')->where('operatore_id', 4)->where('reparto_id', 6)->exists()) {
            DB::table('operatore_reparto')->insert([
                'operatore_id' => 4, 'reparto_id' => 6, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Rimuovi fustella cilindrica da Gennaro
        $gennaro = DB::table('operatori')->where('nome', 'Gennaro')->where('cognome', 'La Scala')->first();
        if ($gennaro) {
            DB::table('operatore_reparto')->where('operatore_id', $gennaro->id)->where('reparto_id', 16)->delete();
        }
    }
};
