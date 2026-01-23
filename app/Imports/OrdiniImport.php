<?php

namespace App\Imports;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OrdiniImport implements ToModel, WithHeadingRow
{
    /**
     * Specifica la riga delle intestazioni nel file Excel
     */
    public function headingRow(): int
    {
        return 2; // le intestazioni sono alla riga 2
    }

    public function model(array $row)
    {


        // Conversione date Excel in oggetti DateTime
        $dataRegistrazione = isset($row['dataregistrazione']) ? Date::excelToDateTimeObject($row['dataregistrazione']) : null;
        $dataPrevista = isset($row['datapresconsegna']) ? Date::excelToDateTimeObject($row['datapresconsegna']) : null;

        // Creo un nuovo ordine (anche duplicati)
        $ordine = Ordine::create([
            'commessa' => $row['codcommessa'] ?? null,
            'cliente_nome' => $row['cliente'] ?? null,
            'cod_art' => $row['codart'] ?? null,
            'descrizione' => $row['descrizione'] ?? null,
            'qta_richiesta' => $row['qta'] ?? 0,
            'um' => $row['um'] ?? 'FG',
            'data_registrazione' => $dataRegistrazione,
            'data_prevista_consegna' => $dataPrevista,
            'priorita' => $row['priorita'] ?? 0,
            'quantita' => $row['qta'] ?? 0,
            'cod_carta' => $row['codcarta'] ?? null,
            'qta_fase' => $row['qtafase'] ?? 0,
            'carta' => $row['carta'] ?? null,
            'qta_carta' => $row['qtacarta'] ?? 0,
            'UM_carta' => $row['umcarta'] ?? null,
        ]);

      

        // Mappa fasi → reparti
       $mappaReparti = [
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

    'AVVIAMENTISTAMPA.EST1.1' => 'stampa offset',
    'blocchi.manuale' => 'esterno',
    'BROSSCOPBANDELLAEST' => 'esterno',
    'BROSSCOPEST' => 'esterno',
    'BROSSFILOREFE/A4EST' => 'esterno',
    'BROSSFILOREFE/A5EST' => 'esterno',

    'BRT1' => 'spedizione',
    'brt1' => 'spedizione',

    'CARTONATO.GEN' => 'esterno',

    'CORDONATURAPETRATTO' => 'legatoria',
    'DEKIA-Difficile' => 'legatoria',

    'FIN01' => 'legatoria',
    'FIN03' => 'legatoria',
    'FIN04' => 'legatoria',

    'FOIL.MGI.30M' => 'digitale',
    'FOILMGI' => 'digitale',

    'FUST.STARPACK.74X104' => 'esterno',
    'FUSTBIML75X106' => 'fustella',
    'FUSTbIML75X106' => 'fustella',
    'FUSTBOBST75X106' => 'fustella',
    'FUSTBOBSTRILIEVI' => 'fustella',
    'FUSTSTELG33.44' => 'fustella',
    'FUSTSTELP25.35' => 'fustella',
    'FUSTIML75X106' => 'fustella',
    'FUSTELLATURA72X51' => 'fustella',
    'FINESTRATURA.INT'=>'finestre',

    'INCOLLAGGIO.PATTINA' => 'legatoria',
    'INCOLLAGGIOBLOCCHI' => 'legatoria',
    'LAVGEN' => 'legatoria',

    'NUM.PROGR.' => 'legatoria',
    'NUM33.44' => 'legatoria',
    'PERF.BUC' => 'legatoria',

    'PI01' => 'piegaincolla',
    'PI02' => 'piegaincolla',
    'PI03' => 'piegaincolla',

    'PIEGA2ANTECORDONE' => 'legatoria',
    'PIEGA2ANTESINGOLO' => 'legatoria',
    'PIEGA3ANTESINGOLO' => 'legatoria',
    'PIEGA8ANTESINGOLO' => 'legatoria',
    'PIEGAMANUALE' => 'legatoria',

    'PLALUX1LATO' => 'plastificazione',
    'PLALUXBV' => 'plastificazione',
    'PLAOPA1LATO' => 'plastificazione',
    'PLAOPABV' => 'plastificazione',
    'PLAPOLIESARG1LATO' => 'plastificazione',
    'PLASAB1LATO' => 'plastificazione',
    'PLASABBIA1LATO' => 'plastificazione',
    'PLASOFTBV' => 'plastificazione',
    'PLASOFTBVEST' => 'plastificazione',
    'PLASOFTTOUCH1' => 'plastificazione',

    'PUNTOMETALLICO' => 'legatoria',
    'PUNTOMETALLICOEST' => 'legatoria',
    'PUNTOMETALLICOESTCOPERT.' => 'legatoria',
    'PUNTOMETAMANUALE' => 'legatoria',

    'RILIEVOASECCOJOH' => 'fustella',

    'SFUST' => 'legatoria',
    'SFUST.IML.FUSTELLATO' => 'legatoria',

    'SPIRBLOCCOLIBROA3' => 'legatoria',
    'SPIRBLOCCOLIBROA4' => 'legatoria',
    'SPIRBLOCCOLIBROA5' => 'legatoria',

    'STAMPA' => 'stampa offset',
    'STAMPA.OFFSET11.EST' => 'esterno',
    'STAMPABUSTE.EST' => 'esterno',
    'STAMPACALDOJOH' => 'stampa a caldo',
    'STAMPACALDOJOH0,1' => 'stampa a caldo',

    'STAMPAINDIGO' => 'digitale',
    'STAMPAINDIGOBN' => 'digitale',

    'STAMPAXL106' => 'stampa offset',
    'STAMPAXL106.1' => 'stampa offset',
    'STAMPAXL106.2' => 'stampa offset',
    'STAMPAXL106.3' => 'stampa offset',
    'STAMPAXL106.4' => 'stampa offset',
    'STAMPAXL106.5' => 'stampa offset',
    'STAMPAXL106.6' => 'stampa offset',
    'STAMPAXL106.7' => 'stampa offset',

    'TAGLIACARTE' => 'legatoria',
    'TAGLIACARTE.IML' => 'legatoria',
    'TAGLIOINDIGO' => 'legatoria',

    'UVSERIGRAFICOEST' => 'esterno',
    'UVSPOT.MGI.30M' => 'digitale',
    'UVSPOT.MGI.9M' => 'digitale',
    'UVSPOTEST' => 'digitale',
    'UVSPOTSPESSEST' => 'digitale',

    'ZUND' => 'digitale',
    'APPL.CORDONCINO0,035' => 'legatoria',
];

        // Creo una nuova fase collegata (anche duplicati)
        if (!empty($row['fase'])) {
            $faseCatalogo = FasiCatalogo::firstOrCreate(
                ['nome' => $row['fase']],
                ['reparto_id' => 1]
            );

            $reparto = $mappaReparti[$row['fase']] ?? 'generico';

            OrdineFase::create([
                'ordine_id' => $ordine->id,
                'fase_catalogo_id' => $faseCatalogo->id,
                'fase' => $row['fase'],
                'reparto' => $reparto,
                'qta_prod' =>0,
                'note' => null,
                'stato' =>  0,
            ]);
        }

        return $ordine;
    }
}