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

        // Drop UNIQUE + FK via raw SQL (Laravel Schema non riesce con FK composte)
        // Prima lista le FK sulla tabella per trovare quella su ubicazione_id
        $fks = DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'magazzino_giacenze'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE magazzino_giacenze DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        DB::statement("ALTER TABLE magazzino_giacenze DROP INDEX `magazzino_giacenze_articolo_id_ubicazione_id_lotto_unique`");
        DB::table('magazzino_giacenze')->whereNull('ubicazione_id')->update(['ubicazione_id' => 0]);
        DB::statement("ALTER TABLE magazzino_giacenze MODIFY `ubicazione_id` BIGINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE magazzino_giacenze ADD UNIQUE `mag_giac_art_ub_lotto_unique` (`articolo_id`, `ubicazione_id`, `lotto`)");

        // Ricrea FK articolo_id (ubicazione_id non ha più FK dato che 0 non è un ID valido)
        Schema::table('magazzino_giacenze', function (Blueprint $table) {
            $table->foreign('articolo_id')->references('id')->on('magazzino_articoli');
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
