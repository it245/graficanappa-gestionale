<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge indici di performance su ordine_fasi e ordini per LCP dashboard operatore/owner.
 * Query target:
 *   WHERE stato < 3 AND fase_catalogo_id IN (...)
 *   WHERE data_fine >= release_def2
 *   whereDoesntHave('ordine.fasi', scarico_eseguito = true)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $t) {
            // Solo se non esistono già
            $existing = collect(DB::select("SHOW INDEX FROM ordine_fasi"))->pluck('Key_name')->unique()->toArray();

            if (!in_array('idx_of_stato_fase_catalogo', $existing)) {
                $t->index(['stato', 'fase_catalogo_id'], 'idx_of_stato_fase_catalogo');
            }
            if (!in_array('idx_of_data_fine', $existing)) {
                $t->index('data_fine', 'idx_of_data_fine');
            }
            if (!in_array('idx_of_esterno', $existing)) {
                $t->index('esterno', 'idx_of_esterno');
            }
            if (!in_array('idx_of_scarico_eseguito', $existing) && Schema::hasColumn('ordine_fasi', 'scarico_eseguito')) {
                $t->index('scarico_eseguito', 'idx_of_scarico_eseguito');
            }
            if (!in_array('idx_of_ordine_id_stato', $existing)) {
                $t->index(['ordine_id', 'stato'], 'idx_of_ordine_id_stato');
            }
        });

        Schema::table('ordini', function (Blueprint $t) {
            $existing = collect(DB::select("SHOW INDEX FROM ordini"))->pluck('Key_name')->unique()->toArray();
            if (!in_array('idx_ord_commessa', $existing)) {
                $t->index('commessa', 'idx_ord_commessa');
            }
            if (!in_array('idx_ord_data_registrazione', $existing)) {
                $t->index('data_registrazione', 'idx_ord_data_registrazione');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $t) {
            $t->dropIndex('idx_of_stato_fase_catalogo');
            $t->dropIndex('idx_of_data_fine');
            $t->dropIndex('idx_of_esterno');
            $t->dropIndex('idx_of_scarico_eseguito');
            $t->dropIndex('idx_of_ordine_id_stato');
        });
        Schema::table('ordini', function (Blueprint $t) {
            $t->dropIndex('idx_ord_commessa');
            $t->dropIndex('idx_ord_data_registrazione');
        });
    }
};
