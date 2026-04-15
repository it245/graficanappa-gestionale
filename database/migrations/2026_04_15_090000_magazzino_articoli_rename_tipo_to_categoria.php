<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Magazzino gestisce tutti i materiali (carta, foil, scatoloni), non solo carta.
 * Rinomina tipo_carta → categoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            $table->renameColumn('tipo_carta', 'categoria');
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            $table->renameColumn('categoria', 'tipo_carta');
        });
    }
};
