<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance fix: aggiunge indici critici mancanti.
 * ordine_fasi è la tabella più interrogata (3800+ righe) ma ha ZERO indici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            // Composito: la query più comune è WHERE ordine_id = ? AND stato = ?
            $table->index(['ordine_id', 'stato'], 'idx_of_ordine_stato');

            // Composito: usato per dedup e lookup per fase_catalogo
            $table->index(['fase_catalogo_id', 'stato'], 'idx_of_fase_cat_stato');

            // Soft delete: ogni query aggiunge WHERE deleted_at IS NULL
            $table->index('deleted_at', 'idx_of_deleted');

            // Singoli: usati frequentemente in WHERE e ORDER BY
            $table->index('stato', 'idx_of_stato');
            $table->index('operatore_id', 'idx_of_operatore');
        });

        // fasi_catalogo.reparto_id: usato in 20+ whereHas
        Schema::table('fasi_catalogo', function (Blueprint $table) {
            $table->index('reparto_id', 'idx_fc_reparto');
        });
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropIndex('idx_of_ordine_stato');
            $table->dropIndex('idx_of_fase_cat_stato');
            $table->dropIndex('idx_of_deleted');
            $table->dropIndex('idx_of_stato');
            $table->dropIndex('idx_of_operatore');
        });

        Schema::table('fasi_catalogo', function (Blueprint $table) {
            $table->dropIndex('idx_fc_reparto');
        });
    }
};
