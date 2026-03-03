<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $repartoFustellaPiana = DB::table('reparti')->where('nome', 'fustella piana')->value('id');
        if (!$repartoFustellaPiana) return;

        DB::table('fasi_catalogo')
            ->where('nome', 'RILIEVOASECCOJOH')
            ->update(['reparto_id' => $repartoFustellaPiana]);
    }

    public function down(): void
    {
        $repartoFustella = DB::table('reparti')->where('nome', 'fustella')->value('id');
        if (!$repartoFustella) return;

        DB::table('fasi_catalogo')
            ->where('nome', 'RILIEVOASECCOJOH')
            ->update(['reparto_id' => $repartoFustella]);
    }
};
