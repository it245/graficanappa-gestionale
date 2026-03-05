<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rinomina reparto "finestre" → "finestratura"
        DB::table('reparti')
            ->where('nome', 'finestre')
            ->update(['nome' => 'finestratura']);

        // Aggiorna fasi_catalogo FIN01/FIN03/FIN04 che erano in "legatoria" → ora in "finestratura"
        $repartoFinestratura = DB::table('reparti')->where('nome', 'finestratura')->first();
        if ($repartoFinestratura) {
            DB::table('fasi_catalogo')
                ->whereIn('nome', ['FIN01', 'FIN03', 'FIN04'])
                ->update(['reparto_id' => $repartoFinestratura->id]);
        }
    }

    public function down(): void
    {
        DB::table('reparti')
            ->where('nome', 'finestratura')
            ->update(['nome' => 'finestre']);
    }
};
