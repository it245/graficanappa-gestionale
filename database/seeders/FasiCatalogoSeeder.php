<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

class FasiCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $mappa = [

            // ESTERNO
            'accopp+fust' => 'esterno',
            'ACCOPPIATURA.FOG.33.48INT' => 'esterno',
            'ACCOPPIATURA.FOGLI' => 'esterno',
            'Allest.Manuale' => 'esterno',
            'ALLEST.SHOPPER' => 'esterno',
            'ALLEST.SHOPPER030' => 'esterno',
            'ALLESTIMENTO.ESPOSITORI' => 'esterno',
            'APPL.BIADESIVO30' => 'esterno',
            'appl.laccetto' => 'esterno',
            'ARROT2ANGOLI' => 'esterno',
            'ARROT4ANGOLI' => 'esterno',

            // STAMPA OFFSET
            'AVVIAMENTISTAMPA.EST1.1' => 'stampa offset',
            'STAMPA' => 'stampa offset',
            'STAMPAXL106' => 'stampa offset',
            'STAMPAXL106.1' => 'stampa offset',
            'STAMPAXL106.2' => 'stampa offset',
            'STAMPAXL106.3' => 'stampa offset',
            'STAMPAXL106.4' => 'stampa offset',
            'STAMPAXL106.5' => 'stampa offset',
            'STAMPAXL106.6' => 'stampa offset',
            'STAMPAXL106.7' => 'stampa offset',

            // DIGITALE
            'FOIL.MGI.30M' => 'digitale',
            'FOILMGI' => 'digitale',
            'STAMPAINDIGO' => 'digitale',
            'STAMPAINDIGOBN' => 'digitale',
            'UVSPOT.MGI.30M' => 'digitale',
            'UVSPOT.MGI.9M' => 'digitale',
            'ZUND' => 'digitale',

            // FUSTELLA
            'FUSTBIML75X106' => 'fustella piana',
            'FUSTbIML75X106' => 'fustella piana',
            'FUSTBOBST75X106' => 'fustella piana',
            'FUSTBOBSTRILIEVI' => 'fustella piana',
            'FUSTSTELG33.44' => 'fustella cilindrica',
            'FUSTSTELP25.35' => 'fustella cilindrica',
            'RILIEVOASECCOJOH' => 'rilievo',

            // FINESTRE
            'FIN01' => 'finestre',
            'FIN03' => 'finestre',
            'FIN04' => 'finestre',
            'FINESTRATURA.MANUALE' => 'finestre',
            'FINESTRATURA.INT' => 'finestre',

            // FINITURA DIGITALE
            'CORDONATURAPETRATTO' => 'finitura digitale',
            'DEKIA-Difficile' => 'finitura digitale',
            'DEKIA-semplice' => 'finitura digitale',
            'PIEGA2ANTECORDONE' => 'finitura digitale',

            // LEGATORIA
            'INCOLLAGGIO.PATTINA' => 'legatoria',
            'INCOLLAGGIOBLOCCHI' => 'legatoria',
            'NUM.PROGR.' => 'legatoria',
            'PERF.BUC' => 'legatoria',
            'PIEGA2ANTECORDONE' => 'legatoria',
            'PIEGA2ANTESINGOLO' => 'legatoria',
            'PIEGA3ANTESINGOLO' => 'legatoria',
            'PIEGA8ANTESINGOLO' => 'legatoria',
            'PIEGA8TTAVO' => 'legatoria',
            'PIEGAMANUALE' => 'legatoria',
            'PUNTOMETALLICO' => 'legatoria',
            'PUNTOMETAMANUALE' => 'legatoria',
            'TAGLIACARTE' => 'legatoria',
            'TAGLIACARTE.IML' => 'legatoria',
            'TAGLIOINDIGO' => 'legatoria',

            // PIEGAINCOLLA
            'PI01' => 'piegaincolla',
            'PI02' => 'piegaincolla',
            'PI03' => 'piegaincolla',

            // PLASTIFICAZIONE
            'PLALUX1LATO' => 'plastificazione',
            'PLALUXBV' => 'plastificazione',
            'PLAOPA1LATO' => 'plastificazione',
            'PLAOPABV' => 'plastificazione',
            'PLASOFTBV' => 'plastificazione',

            // SPEDIZIONE
            'BRT1' => 'spedizione',
            'brt1' => 'spedizione',
        ];

        foreach ($mappa as $fase => $nomeReparto) {

            $reparto = Reparto::where('nome', $nomeReparto)->first();

            if (!$reparto) {
                $this->command->warn("Reparto NON trovato: {$nomeReparto}");
                continue;
            }

            FasiCatalogo::updateOrCreate(
                ['nome' => $fase],
                ['reparto_id' => $reparto->id]
            );
        }
    }
}