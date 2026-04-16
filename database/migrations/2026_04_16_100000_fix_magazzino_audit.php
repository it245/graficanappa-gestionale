<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix audit magazzino: decimal quantità, UNIQUE NULL, soglia decimal,
 * indici mancanti, categoria NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. magazzino_articoli: soglia_minima da integer a decimal, categoria NOT NULL
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            $table->decimal('soglia_minima', 10, 2)->default(0)->change();
        });
        // Default 'carta' per categoria NULL
        DB::table('magazzino_articoli')->whereNull('categoria')->update(['categoria' => 'carta']);

        // 2. magazzino_giacenze: quantita da integer a decimal, fix UNIQUE con NULL
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->decimal('quantita', 12, 2)->default(0)->change();
        });

        // Rimuovi FK su ubicazione_id prima di droppare UNIQUE, poi ricrea
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->dropForeign(['ubicazione_id']);
        });
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->dropUnique(['articolo_id', 'ubicazione_id', 'lotto']);
        });
        // Imposta ubicazione_id=0 dove è NULL per evitare duplicati
        DB::table('magazzino_giacenze')->whereNull('ubicazione_id')->update(['ubicazione_id' => 0]);
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->unsignedBigInteger('ubicazione_id')->nullable(false)->default(0)->change();
            $table->unique(['articolo_id', 'ubicazione_id', 'lotto'], 'mag_giac_art_ub_lotto_unique');
        });

        // 3. magazzino_movimenti: quantita da integer a decimal
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->decimal('quantita', 12, 2)->change();
            $table->integer('giacenza_dopo')->change(); // keep as-is, just ensure decimal
        });
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->decimal('giacenza_dopo', 12, 2)->change();
        });

        // 4. magazzino_etichette: quantita_iniziale da integer a decimal
        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->decimal('quantita_iniziale', 12, 2)->change();
        });

        // 5. Indici mancanti
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->index(['articolo_id', 'quantita'], 'idx_mg_art_qta');
        });

        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->index(['articolo_id', 'attiva'], 'idx_me_art_attiva');
        });

        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->index('operatore_id', 'idx_mm_operatore');
        });
    }

    public function down(): void
    {
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->dropIndex('idx_mm_operatore');
        });
        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->dropIndex('idx_me_art_attiva');
        });
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->dropIndex('idx_mg_art_qta');
            $table->dropUnique('mag_giac_art_ub_lotto_unique');
            $table->unique(['articolo_id', 'ubicazione_id', 'lotto']);
            $table->integer('quantita')->default(0)->change();
        });
        Schema::table('magazzino_articoli', function (Blueprint $table) {
            $table->integer('soglia_minima')->default(0)->change();
        });
        Schema::table('magazzino_movimenti', function (Blueprint $table) {
            $table->integer('quantita')->change();
            $table->integer('giacenza_dopo')->change();
        });
        Schema::table('magazzino_etichette', function (Blueprint $table) {
            $table->integer('quantita_iniziale')->change();
        });
    }
};
