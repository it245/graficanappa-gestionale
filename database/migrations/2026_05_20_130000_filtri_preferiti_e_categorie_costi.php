<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // #6 Filtri preferiti analisi costi
        Schema::create('filtri_preferiti_costi', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 100);
            $t->string('autore', 50)->nullable();
            $t->json('filtri'); // array filtri
            $t->timestamps();
        });

        // #12 Categorie altri costi configurabili
        Schema::create('categorie_altri_costi', function (Blueprint $t) {
            $t->id();
            $t->string('nome', 100)->unique();
            $t->string('descrizione', 200)->nullable();
            $t->boolean('attiva')->default(true);
            $t->integer('ordine')->default(0);
            $t->timestamps();
        });

        // seed iniziale (categorie pre-esistenti)
        \Illuminate\Support\Facades\DB::table('categorie_altri_costi')->insert([
            ['nome' => 'cliche',        'descrizione' => 'Realizzazione cliché stampa caldo/lamina',      'ordine' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'fustella',      'descrizione' => 'Costruzione fustella per taglio',               'ordine' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'lastre',        'descrizione' => 'Lastre stampa offset',                          'ordine' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'trasporto',     'descrizione' => 'Spese trasporto interno/esterno',               'ordine' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'lavorazione_esterna', 'descrizione' => 'Lavorazioni date in esterno',             'ordine' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'manodopera_extra', 'descrizione' => 'Manodopera straordinaria',                   'ordine' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'altro',         'descrizione' => 'Altri costi generici',                          'ordine' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('filtri_preferiti_costi');
        Schema::dropIfExists('categorie_altri_costi');
    }
};
