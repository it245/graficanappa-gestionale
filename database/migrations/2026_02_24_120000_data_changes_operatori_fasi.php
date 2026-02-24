<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AVVIAMENTISTAMPA.EST1.1 → reparto esterno (12)
        DB::table('fasi_catalogo')
            ->where('id', 24)
            ->update(['reparto_id' => 12]);

        // 2. Francesco Verde (ID 4) → aggiungere reparto legatoria (6)
        if (!DB::table('operatore_reparto')->where('operatore_id', 4)->where('reparto_id', 6)->exists()) {
            DB::table('operatore_reparto')->insert([
                'operatore_id' => 4,
                'reparto_id'   => 6,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // 3. PUNTOMETALLICOEST (ID 88) → reparto esterno (12)
        DB::table('fasi_catalogo')
            ->where('id', 88)
            ->update(['reparto_id' => 12]);

        // 4. Creare reparto "tagliacarte", spostare 3 fasi, assegnare Santoro
        $repartoTagliacarte = DB::table('reparti')->insertGetId([
            'nome'       => 'tagliacarte',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Spostare TAGLIACARTE (12), TAGLIACARTE.IML (55), TAGLIOINDIGO (56) nel nuovo reparto
        DB::table('fasi_catalogo')
            ->whereIn('id', [12, 55, 56])
            ->update(['reparto_id' => $repartoTagliacarte]);

        // Mario Santoro (ID 26): rimuovere legatoria, aggiungere tagliacarte
        DB::table('operatore_reparto')
            ->where('operatore_id', 26)
            ->where('reparto_id', 6)
            ->delete();

        DB::table('operatore_reparto')->insert([
            'operatore_id' => 26,
            'reparto_id'   => $repartoTagliacarte,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // 5. Giuseppe Torromacco (ID 21) → disattivare
        DB::table('operatori')
            ->where('id', 21)
            ->update(['attivo' => 0]);

        // 6. FUSTIML75X106 (ID 5) → reparto fustella piana (15)
        DB::table('fasi_catalogo')
            ->where('id', 5)
            ->update(['reparto_id' => 15]);

        // 7. Creare Gennaro La Scala, assegnare reparto fustella (5)
        $gennaroId = DB::table('operatori')->insertGetId([
            'nome'              => 'Gennaro',
            'cognome'           => 'La Scala',
            'codice_operatore'  => 'LASCALA',
            'ruolo'             => 'operatore',
            'attivo'            => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('operatore_reparto')->insert([
            'operatore_id' => $gennaroId,
            'reparto_id'   => 5, // fustella
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        // Ripristina AVVIAMENTISTAMPA.EST1.1 a stampa offset (11)
        DB::table('fasi_catalogo')->where('id', 24)->update(['reparto_id' => 11]);

        // Rimuovi legatoria da Francesco Verde
        DB::table('operatore_reparto')->where('operatore_id', 4)->where('reparto_id', 6)->delete();

        // Ripristina PUNTOMETALLICOEST a legatoria (6)
        DB::table('fasi_catalogo')->where('id', 88)->update(['reparto_id' => 6]);

        // Ripristina le 3 fasi tagliacarte a legatoria (6)
        DB::table('fasi_catalogo')->whereIn('id', [12, 55, 56])->update(['reparto_id' => 6]);

        // Mario Santoro: rimuovi tagliacarte, ri-aggiungi legatoria
        $repartoTagliacarte = DB::table('reparti')->where('nome', 'tagliacarte')->first();
        if ($repartoTagliacarte) {
            DB::table('operatore_reparto')->where('operatore_id', 26)->where('reparto_id', $repartoTagliacarte->id)->delete();
            DB::table('reparti')->where('id', $repartoTagliacarte->id)->delete();
        }
        if (!DB::table('operatore_reparto')->where('operatore_id', 26)->where('reparto_id', 6)->exists()) {
            DB::table('operatore_reparto')->insert([
                'operatore_id' => 26, 'reparto_id' => 6, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Riattiva Giuseppe Torromacco
        DB::table('operatori')->where('id', 21)->update(['attivo' => 1]);

        // Ripristina FUSTIML75X106 a fustella (5)
        DB::table('fasi_catalogo')->where('id', 5)->update(['reparto_id' => 5]);

        // Rimuovi Gennaro La Scala
        $gennaro = DB::table('operatori')->where('nome', 'Gennaro')->where('cognome', 'La Scala')->first();
        if ($gennaro) {
            DB::table('operatore_reparto')->where('operatore_id', $gennaro->id)->delete();
            DB::table('operatori')->where('id', $gennaro->id)->delete();
        }
    }
};
