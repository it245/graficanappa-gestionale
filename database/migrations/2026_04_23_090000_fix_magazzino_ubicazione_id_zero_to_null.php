<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix dati legacy: ubicazione_id = 0 → NULL nelle tabelle magazzino.
 * Risolve FK violation su magazzino_movimenti quando giacenza ha 0 invece di NULL.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('magazzino_giacenze')->where('ubicazione_id', 0)->update(['ubicazione_id' => null]);
        DB::table('magazzino_movimenti')->where('ubicazione_id', 0)->update(['ubicazione_id' => null]);
        DB::table('magazzino_etichette')->where('ubicazione_id', 0)->update(['ubicazione_id' => null]);
    }

    public function down(): void
    {
        // nessun rollback: NULL è la forma corretta, non si ripristina 0
    }
};
