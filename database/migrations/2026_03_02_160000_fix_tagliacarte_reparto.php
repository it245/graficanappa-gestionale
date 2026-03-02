<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trova (o crea) il reparto tagliacarte
        $repartoTagliacarte = DB::table('reparti')->where('nome', 'tagliacarte')->first();
        if (!$repartoTagliacarte) {
            $id = DB::table('reparti')->insertGetId(['nome' => 'tagliacarte']);
        } else {
            $id = $repartoTagliacarte->id;
        }

        // Aggiorna tutte le fasi_catalogo TAGLI* al reparto tagliacarte
        DB::table('fasi_catalogo')
            ->whereIn('nome', ['TAGLIACARTE', 'TAGLIACARTE.IML', 'TAGLIOINDIGO'])
            ->update(['reparto_id' => $id]);
    }

    public function down(): void
    {
        // no-op
    }
};
