<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix dati legacy: ubicazione_id = 0 → NULL nelle tabelle magazzino.
 * Risolve FK violation su magazzino_movimenti quando giacenza ha 0 invece di NULL.
 *
 * Su DB legacy ubicazione_id potrebbe essere NOT NULL: rendo nullable prima.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['magazzino_giacenze', 'magazzino_movimenti', 'magazzino_etichette'] as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'ubicazione_id')) continue;

            // Rendi nullable se NOT NULL
            try {
                DB::statement("ALTER TABLE `{$tbl}` MODIFY `ubicazione_id` BIGINT UNSIGNED NULL");
            } catch (\Throwable $e) {
                // Ignora se già nullable o tipo diverso
            }

            DB::table($tbl)->where('ubicazione_id', 0)->update(['ubicazione_id' => null]);
        }
    }

    public function down(): void
    {
        // nessun rollback: NULL è la forma corretta, non si ripristina 0
    }
};
