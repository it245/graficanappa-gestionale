<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $repartoFinDig = DB::table('reparti')->where('nome', 'finitura digitale')->value('id');
        if (!$repartoFinDig) return;

        $fasiDaSpostare = ['ZUND', 'FOIL.MGI.30M', 'FOILMGI'];

        DB::table('fasi_catalogo')
            ->whereIn('nome', $fasiDaSpostare)
            ->update(['reparto_id' => $repartoFinDig]);
    }

    public function down(): void
    {
        $repartoDigitale = DB::table('reparti')->where('nome', 'digitale')->value('id');
        if (!$repartoDigitale) return;

        DB::table('fasi_catalogo')
            ->whereIn('nome', ['ZUND', 'FOIL.MGI.30M', 'FOILMGI'])
            ->update(['reparto_id' => $repartoDigitale]);
    }
};
