<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance v2: indici aggiuntivi per query critiche dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ordine_fasi: composito per query terminate (stato + data_fine)
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->index(['stato', 'data_fine'], 'idx_of_stato_datafine');
            $table->index('priorita', 'idx_of_priorita');
            $table->index('data_fine', 'idx_of_data_fine');
        });

        // fase_operatore: la pivot più interrogata
        Schema::table('fase_operatore', function (Blueprint $table) {
            $table->index('data_fine', 'idx_fo_data_fine');
            $table->index(['operatore_id', 'data_fine'], 'idx_fo_op_datafine');
        });

        // ordini: commessa è usata in JOIN e WHERE ovunque
        Schema::table('ordini', function (Blueprint $table) {
            if (!$this->hasIndex('ordini', 'commessa')) {
                $table->index('commessa', 'idx_ord_commessa');
            }
        });

        // prinect_attivita: query per date e commessa
        if (Schema::hasTable('prinect_attivita')) {
            Schema::table('prinect_attivita', function (Blueprint $table) {
                if (Schema::hasColumn('prinect_attivita', 'commessa_gestionale')) {
                    $table->index('commessa_gestionale', 'idx_pa_commessa');
                }
                if (Schema::hasColumn('prinect_attivita', 'start_time')) {
                    $table->index('start_time', 'idx_pa_start');
                }
            });
        }

        // contatori_stampante: query per data
        if (Schema::hasTable('contatori_stampante')) {
            Schema::table('contatori_stampante', function (Blueprint $table) {
                if (Schema::hasColumn('contatori_stampante', 'rilevato_at')) {
                    $table->index('rilevato_at', 'idx_cs_rilevato');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('ordine_fasi', function (Blueprint $table) {
            $table->dropIndex('idx_of_stato_datafine');
            $table->dropIndex('idx_of_priorita');
            $table->dropIndex('idx_of_data_fine');
        });

        Schema::table('fase_operatore', function (Blueprint $table) {
            $table->dropIndex('idx_fo_data_fine');
            $table->dropIndex('idx_fo_op_datafine');
        });

        Schema::table('ordini', function (Blueprint $table) {
            if ($this->hasIndex('ordini', 'idx_ord_commessa')) {
                $table->dropIndex('idx_ord_commessa');
            }
        });

        if (Schema::hasTable('prinect_attivita')) {
            Schema::table('prinect_attivita', function (Blueprint $table) {
                $table->dropIndex('idx_pa_commessa');
                $table->dropIndex('idx_pa_start');
            });
        }

        if (Schema::hasTable('contatori_stampante')) {
            Schema::table('contatori_stampante', function (Blueprint $table) {
                $table->dropIndex('idx_cs_rilevato');
            });
        }
    }

    private function hasIndex(string $table, string $column): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'])) return true;
        }
        return false;
    }
};
