<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ON DELETE RESTRICT su tutte le FK magazzino.
 * Protegge storico movimenti da cancellazioni accidentali.
 */
return new class extends Migration
{
    public function up(): void
    {
        // magazzino_movimenti: articolo_id, operatore_id
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli')->onDelete('restrict');
        });

        // magazzino_giacenze: articolo_id
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli')->onDelete('restrict');
        });

        // magazzino_etichette: articolo_id, giacenza_id
        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli');
        });
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli');
        });
        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->dropForeign(['articolo_id']);
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli');
        });
    }
};
