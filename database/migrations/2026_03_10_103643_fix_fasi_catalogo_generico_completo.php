<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Svuota reparto "generico" (id 14) spostando ogni fase al reparto corretto
        // 1=spedizione, 4=digitale, 6=legatoria, 10=stampa a caldo, 12=esterno

        $spostamenti = [
            // Stampa a caldo (10)
            'STAMPACALDOJOH0,2'     => 10,
            'STAMPCALDOAJOH'        => 10,
            'STAMPALAMINAORO'       => 10,
            'STAMPASECCO'           => 10,

            // Digitale (4) — UV/MGI
            'UVSPOT.MGI.30M.20'    => 4,
            'UVSPOT.MGI.9M.10'     => 4,

            // Legatoria (6)
            'PMDUPLO40AUTO'         => 6,
            'FASCETTATURA'          => 6,

            // Esterno (12)
            'est STAMPACALDOJOH'           => 12,
            'est FUSTSTELG33.44'           => 12,
            'est FUSTBOBST75X106'          => 12,
            '4graph'                       => 12,
            'ALL.COFANETTO.ISMAsrl'        => 12,
            'PMDUPLO36COP'                 => 12,
            'EXTCLICHESTAMPACALDO'         => 12,
            'EXTCLICHESTAMPA'              => 12,
            'EXTACCOPP.FUST.INCOLL.FOG'    => 12,
            'EXTACCOPPIATURA.FOG.33.48'    => 12,
            'EXTALLEST.SHOPPER024'         => 12,
            'BROSSFRESATA/A5EST'           => 12,
        ];

        foreach ($spostamenti as $nome => $repartoId) {
            DB::table('fasi_catalogo')
                ->where('nome', $nome)
                ->where('reparto_id', 14)
                ->update(['reparto_id' => $repartoId]);
        }
    }

    public function down(): void
    {
        // Non revertiamo
    }
};
