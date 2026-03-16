<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fasi_catalogo', function (Blueprint $table) {
            $table->string('unita_misura', 20)->default('fogli')->after('copie_ora')
                ->comment('fogli o copie/pezzi');
        });

        // Popola unita_misura in base alla fase
        $copie_pezzi = [
            // Piegaincolla
            'PI01', 'PI02', 'PI03',
            // Legatoria / Finitura
            'FIN01', 'FIN03', 'FIN04',
            'PIEGA2ANTECORDONE', 'PIEGA2ANTESINGOLO', 'PIEGA3ANTESINGOLO',
            'PIEGA4ANTESINGOLO', 'PIEGA8ANTESINGOLO', 'PIEGA8TTAVO', 'PIEGAMANUALE',
            'CORDONATURAPETRATTO', 'DEKIA-Difficile', 'DEKIA-semplice',
            'INCOLLAGGIO.PATTINA', 'INCOLLAGGIOBLOCCHI',
            'NUM.PROGR.', 'NUM33.44', 'PERF.BUC', 'LAVGEN',
            'PUNTOMETALLICO', 'PUNTOMETAMANUALE', 'PUNTOMETALLICOEST', 'PUNTOMETALLICOESTCOPERT.',
            'SPIRBLOCCOLIBROA3', 'SPIRBLOCCOLIBROA4', 'SPIRBLOCCOLIBROA5',
            // Allestimento
            'Allest.Manuale', 'ALLEST.SHOPPER', 'ALLEST.SHOPPER030', 'ALLESTIMENTO.ESPOSITORI',
            'APPL.BIADESIVO30', 'APPL.CORDONCINO0,035', 'appl.laccetto',
            'ARROT2ANGOLI', 'ARROT4ANGOLI',
            // Brossura / Cartonato
            'BROSSCOPBANDELLAEST', 'BROSSCOPEST', 'BROSSFILOREFE/A4EST', 'BROSSFILOREFE/A5EST',
            'CARTONATO.GEN',
            // Rilievo
            'RILIEVOASECCOJOH',
            // Spedizione
            'BRT1', 'brt1',
        ];

        DB::table('fasi_catalogo')
            ->whereIn('nome', $copie_pezzi)
            ->update(['unita_misura' => 'copie/pezzi']);
    }

    public function down(): void
    {
        Schema::table('fasi_catalogo', function (Blueprint $table) {
            $table->dropColumn('unita_misura');
        });
    }
};
