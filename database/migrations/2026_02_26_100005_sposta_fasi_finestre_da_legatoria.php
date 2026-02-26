<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sposta le fasi FIN01, FIN03, FIN04, FINESTRATURA.MANUALE, FINESTRATURA.INT
     * dal reparto "legatoria" (id 6) al reparto "finestre" (id 13).
     */
    public function up(): void
    {
        $finestreId = DB::table('reparti')->where('nome', 'finestre')->value('id');
        if (!$finestreId) return;

        DB::table('fasi_catalogo')
            ->whereIn('nome', ['FIN01', 'FIN03', 'FIN04', 'FINESTRATURA.MANUALE', 'FINESTRATURA.INT'])
            ->update(['reparto_id' => $finestreId]);
    }

    /**
     * Reverse: rimetti sotto legatoria.
     */
    public function down(): void
    {
        $legatoriaId = DB::table('reparti')->where('nome', 'legatoria')->value('id');
        if (!$legatoriaId) return;

        DB::table('fasi_catalogo')
            ->whereIn('nome', ['FIN01', 'FIN03', 'FIN04', 'FINESTRATURA.MANUALE', 'FINESTRATURA.INT'])
            ->update(['reparto_id' => $legatoriaId]);
    }
};
