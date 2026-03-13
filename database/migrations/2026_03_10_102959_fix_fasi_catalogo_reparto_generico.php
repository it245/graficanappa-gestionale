<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fasi erroneamente assegnate a "generico" (id 14)
        // PIEGA -> legatoria (id 6)
        DB::table('fasi_catalogo')->where('nome', 'PIEGA8TTAVO')->update(['reparto_id' => 6]);
        DB::table('fasi_catalogo')->where('nome', 'PIEGA4ANTESINGOLO')->update(['reparto_id' => 6]);
        // BRT -> spedizione (id 1)
        DB::table('fasi_catalogo')->where('nome', 'BRT')->update(['reparto_id' => 1]);
        // EXT -> esterno (id 12)
        DB::table('fasi_catalogo')->where('nome', 'EXTCLICHESTAMPACALDO1')->update(['reparto_id' => 12]);
        DB::table('fasi_catalogo')->where('nome', 'EXTPUNTOMETALLICOESTCOPER')->update(['reparto_id' => 12]);
    }

    public function down(): void
    {
        DB::table('fasi_catalogo')->whereIn('nome', [
            'PIEGA8TTAVO', 'PIEGA4ANTESINGOLO', 'BRT',
            'EXTCLICHESTAMPACALDO1', 'EXTPUNTOMETALLICOESTCOPER',
        ])->update(['reparto_id' => 14]);
    }
};
