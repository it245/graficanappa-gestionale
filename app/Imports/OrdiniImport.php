<?php

namespace App\Imports;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;
class OrdiniImport implements ToModel, WithHeadingRow, WithChunkReading
{
    public function chunkSize(): int { return 200; }
    public function headingRow(): int { return 2; }

   public function model(array $row)
{
    $commessa     = isset($row['codcommessa']) ? trim($row['codcommessa']) : null;
    $codArt       = isset($row['codart']) ? trim($row['codart']) : null;
    $faseNome     = isset($row['fase']) ? trim($row['fase']) : null;
    $descrizione  = $row['descrizione'] ?? null;

    if (!$commessa || !$faseNome) return null;

    // --- 1. GESTIONE ORDINE ---
    $ordine = Ordine::updateOrCreate(
        [
            'commessa'    => $commessa,
            'cod_art'     => $codArt,
            'descrizione' => $descrizione,
        ],
        [
            'cliente_nome'           => $row['cliente'] ?? null,
            'data_registrazione'     => !empty($row['dataregistrazione']) ? $this->convertiData($row['dataregistrazione']) : null,
            'data_prevista_consegna' => !empty($row['datapresconsegna']) ? $this->convertiData($row['datapresconsegna']) : null,
            'qta_richiesta'          => $row['qta'] ?? 0,
            'cod_carta'              => $row['codcarta'] ?? null,
            'carta'                  => $row['carta'] ?? null,
            'qta_carta'              => $row['qtacarta'] ?? 0,
            'UM_carta'               => $row['umcarta'] ?? null, // ora importato correttamente
        ]
    );

    // --- 2. REPARTO E TIPO ---
    $mappaReparti = $this->getMappaReparti();
    $tipiFase     = $this->getTipoReparto();
    
    $repartoNome = $mappaReparti[$faseNome] ?? 'generico';
    $tipo        = $tipiFase[$faseNome] ?? 'monofase';

    // --- 3. PRIORITÀ FASE (da config) ---
    $mappaPriorita = config('fasi_priorita');
    $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

    // --- 4. LOGICA FASI CON DEDUPLICAZIONE ---
    $dataFase = [
    'ordine_id'   => $ordine->id,
    'fase'        => $faseNome,
    'reparto_id'  => Reparto::firstOrCreate(
        ['nome' => $repartoNome]
    )->id,
    'qta_fase'    => $row['qtafase'] ?? 0,
    'um'          => $row['umfase'] ?? ($row['um'] ?? 'FG'),
    'priorita'    => $prioritaFase,
    'fase_catalogo_id' => FasiCatalogo::firstOrCreate(
        ['nome' => $faseNome],
        ['reparto_id' => Reparto::firstOrCreate(
            ['nome' => $repartoNome]
        )->id]
    )->id,
];

    if ($tipo === 'monofase') {
        $faseEsistente = OrdineFase::where('ordine_id', $ordine->id)
            ->where('fase', $faseNome)
            ->whereHas('ordine', fn($q) => $q->where('descrizione', $descrizione))
            ->first();

        if ($faseEsistente) {
            $faseEsistente->update($dataFase);
        } else {
            OrdineFase::create($dataFase);
        }

    } elseif ($tipo === 'max 2 fasi') {
        $count = OrdineFase::where('ordine_id', $ordine->id)
            ->where('fase', $faseNome)
            ->whereHas('ordine', fn($q) => $q->where('descrizione', $descrizione))
            ->count();

        if ($count < 2) {
            OrdineFase::create($dataFase);
        }

    } else {
        // multifase: crea sempre
        OrdineFase::create($dataFase);
    }

    return $ordine;
}

// -----------------
// Funzione helper per le date
private function convertiData($valore) {
    if (is_numeric($valore)) {
        // Excel serial number
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valore);
    } elseif (is_string($valore)) {
        // stringa in formato italiano
        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $valore);
        } catch (\Exception $e) {
            // prova senza secondi
            try {
                return Carbon::createFromFormat('d/m/Y H:i', $valore);
            } catch (\Exception $e2) {
                return null; // fallback se formato non valido
            }
        }
    }
    return null;
}
    private function getMappaReparti(): array {
        return [
            // mappa completa come già definita nel tuo codice
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
    'FUSTBIML75X106' => 'fustella piana',
    'FUSTbIML75X106' => 'fustella piana',
    'FUSTBOBST75X106' => 'fustella piana',
    'FUSTBOBSTRILIEVI' => 'fustella piana',
    'FUSTSTELG33.44' => 'fustella cilindrica',
    'FUSTSTELP25.35' => 'fustella cilindrica',
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
    'PIEGA8TTAVO' => 'legatoria',
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

    // Nuove fasi
    '4graph' => 'esterno',
    'stampalaminaoro' => 'stampa a caldo',
    'STAMPALAMINAORO' => 'stampa a caldo',
    'ALL.COFANETTO.ISMAsrl' => 'esterno',
    'PMDUPLO36COP' => 'esterno',
    'FINESTRATURA.MANUALE' => 'finestre',
    'STAMPACALDOJOHEST' => 'esterno',
    'BROSSFRESATA/A5EST' => 'esterno',
    'PIEGA6ANTESINGOLO' => 'legatoria',

    // Fasi con "est"/EXT → esterno
    'est STAMPACALDOJOH' => 'esterno',
    'est FUSTSTELG33.44' => 'esterno',
    'est FUSTBOBST75X106' => 'esterno',
    'STAMPA.ESTERNA' => 'esterno',
    'EXTALL.COFANETTO.LEGOKART' => 'esterno',
    'EXTAllest.Manuale' => 'esterno',
    'EXTALLEST.SHOPPER' => 'esterno',
    'EXTALLESTIMENTO.ESPOSITOR' => 'esterno',
    'EXTAPPL.CORDONCINO0,035' => 'esterno',
    'EXTAVVIAMENTISTAMPA.EST1.' => 'esterno',
    'EXTBROSSCOPEST' => 'esterno',
    'EXTBROSSFILOREFE/A4EST' => 'esterno',
    'EXTBROSSFILOREFE/A5EST' => 'esterno',
    'EXTBROSSFRESATA/A4EST' => 'esterno',
    'EXTBROSSFRESATA/A5EST' => 'esterno',
    'EXTCARTONATO' => 'esterno',
    'EXTCARTONATO.GEN' => 'esterno',
    'EXTFUSTELLATURA72X51' => 'esterno',
    'EXTPUNTOMETALLICOEST' => 'esterno',
    'EXTSTAMPA.OFFSET11.EST' => 'esterno',
    'EXTSTAMPABUSTE.EST' => 'esterno',
    'EXTSTAMPASECCO' => 'esterno',
    'EXTUVSPOTEST' => 'esterno',
    'EXTUVSPOTSPESSEST' => 'esterno',

    // Altre fasi nuove
    'DEKIA-semplice' => 'legatoria',
    'STAMPASECCO' => 'fustella',
    'STAMPACALDO04' => 'stampa a caldo',
    'STAMPACALDOBR' => 'stampa a caldo',
    'STAMPAINDIGOBIANCO' => 'digitale',
        ];
    }

    private function getTipoReparto(): array {
        return [
               // multifase
    'accopp+fust' => 'multifase',
    'FIN01' => 'multifase',
    'FIN03' => 'multifase',
    'FIN04' => 'multifase',
    'PI01' => 'multifase',
    'PI02' => 'multifase',
    'PI03' => 'multifase',
    'TAGLIACARTE' => 'multifase',
    'TAGLIACARTE.IML' => 'multifase',
    'TAGLIOINDIGO' => 'multifase',
    'SFUST' => 'multifase',
    'SFUST.IML.FUSTELLATO' => 'multifase',

    // monofase
    'ACCOPPIATURA.FOG.33.48INT' => 'monofase',
    'ACCOPPIATURA.FOGLI' => 'monofase',
    'Allest.Manuale' => 'monofase',
    'ALLEST.SHOPPER' => 'monofase',
    'ALLEST.SHOPPER030' => 'monofase',
    'APPL.BIADESIVO30' => 'monofase',
    'appl.laccetto' => 'monofase',
    'ARROT2ANGOLI' => 'monofase',
    'ARROT4ANGOLI' => 'monofase',
    'AVVIAMENTISTAMPA.EST1.1' => 'monofase',
    'blocchi.manuale' => 'monofase',
    'BROSSCOPBANDELLAEST' => 'monofase',
    'BROSSCOPEST' => 'monofase',
    'BROSSFILOREFE/A4EST' => 'monofase',
    'BROSSFILOREFE/A5EST' => 'monofase',
    'BRT1' => 'monofase',
    'brt1' => 'monofase',
    'CARTONATO.GEN' => 'monofase',
    'CORDONATURAPETRATTO' => 'monofase',
    'DEKIA-Difficile' => 'monofase',
    'FOIL.MGI.30M' => 'monofase',
    'FOILMGI' => 'monofase',
    'FUST.STARPACK.74X104' => 'monofase',
    'FUSTBIML75X106' => 'monofase',
    'FUSTbIML75X106' => 'monofase',
    'FUSTBOBST75X106' => 'monofase',
    'FUSTBOBSTRILIEVI' => 'monofase',
    'FUSTSTELG33.44' => 'monofase',
    'FUSTSTELP25.35' => 'monofase',
    'INCOLLAGGIO.PATTINA' => 'monofase',
    'INCOLLAGGIOBLOCCHI' => 'monofase',
    'LAVGEN' => 'monofase',
    'NUM.PROGR.' => 'monofase',
    'NUM33.44' => 'monofase',
    'PERF.BUC' => 'monofase',
    'PIEGA2ANTECORDONE' => 'monofase',
    'PIEGA2ANTESINGOLO' => 'monofase',
     'PIEGA3ANTESINGOLO' => 'monofase',
     'PIEGA8ANTESINGOLO' => 'monofase',
     'PIEGA8TTAVO' => 'monofase',
     'PIEGAMANUALE' => 'monofase',
    'PLALUX1LATO' => 'monofase',
    'PLALUXBV' => 'monofase',
    'PLAOPA1LATO' => 'monofase',
    'PLAOPABV' => 'monofase',
    'PLAPOLIESARG1LATO' => 'monofase',
    'PLASAB1LATO' => 'monofase',
    'PLASABBIA1LATO' => 'monofase',
    'PLASOFTBV' => 'monofase',
    'PLASOFTBVEST' => 'monofase',
    'PLASOFTTOUCH1' => 'monofase',
    'PUNTOMETALLICO' => 'monofase',
    'PUNTOMETALLICOEST' => 'monofase',
    'PUNTOMETALLICOESTCOPERT.' => 'monofase',
    'PUNTOMETAMANUALE' => 'monofase',
    'RILIEVOASECCOJOH' => 'monofase',
    'SPIRBLOCCOLIBROA3' => 'monofase',
    'SPIRBLOCCOLIBROA4' => 'monofase',
    'SPIRBLOCCOLIBROA5' => 'monofase',
    'STAMPA' => 'monofase',
    'STAMPA.OFFSET11.EST' => 'monofase',
    'STAMPABUSTE.EST' => 'monofase',
    'STAMPACALDOJOH' => 'monofase',
    'STAMPACALDOJOH0,1' => 'monofase',
    'UVSERIGRAFICOEST' => 'monofase',
    'UVSPOT.MGI.30M' => 'monofase',
    'UVSPOT.MGI.9M' => 'monofase',
    'UVSPOTEST' => 'monofase',
    'UVSPOTSPESSEST' => 'monofase',
    'ZUND' => 'monofase',
    'APPL.CORDONCINO0,035' => 'monofase',

    // nuove fasi
    '4graph' => 'monofase',
    'stampalaminaoro' => 'monofase',
    'STAMPALAMINAORO' => 'monofase',
    'ALL.COFANETTO.ISMAsrl' => 'monofase',
    'PMDUPLO36COP' => 'monofase',
    'FINESTRATURA.MANUALE' => 'monofase',
    'STAMPACALDOJOHEST' => 'monofase',
    'BROSSFRESATA/A5EST' => 'monofase',
    'PIEGA6ANTESINGOLO' => 'monofase',

    // max 2 fasi
    'STAMPAINDIGO' => 'max 2 fasi',
    'STAMPAINDIGOBN' => 'max 2 fasi',
    'STAMPAXL106' => 'max 2 fasi',
    'STAMPAXL106.1' => 'max 2 fasi',
    'STAMPAXL106.2' => 'max 2 fasi',
    'STAMPAXL106.3' => 'max 2 fasi',
    'STAMPAXL106.4' => 'max 2 fasi',
    'STAMPAXL106.5' => 'max 2 fasi',
    'STAMPAXL106.6' => 'max 2 fasi',
    'STAMPAXL106.7' => 'max 2 fasi',
    'STAMPAINDIGOBIANCO' => 'max 2 fasi',

    // Nuove fasi EXT e altre → monofase
    'est STAMPACALDOJOH' => 'monofase',
    'est FUSTSTELG33.44' => 'monofase',
    'est FUSTBOBST75X106' => 'monofase',
    'STAMPA.ESTERNA' => 'monofase',
    'EXTALL.COFANETTO.LEGOKART' => 'monofase',
    'EXTAllest.Manuale' => 'monofase',
    'EXTALLEST.SHOPPER' => 'monofase',
    'EXTALLESTIMENTO.ESPOSITOR' => 'monofase',
    'EXTAPPL.CORDONCINO0,035' => 'monofase',
    'EXTAVVIAMENTISTAMPA.EST1.' => 'monofase',
    'EXTBROSSCOPEST' => 'monofase',
    'EXTBROSSFILOREFE/A4EST' => 'monofase',
    'EXTBROSSFILOREFE/A5EST' => 'monofase',
    'EXTBROSSFRESATA/A4EST' => 'monofase',
    'EXTBROSSFRESATA/A5EST' => 'monofase',
    'EXTCARTONATO' => 'monofase',
    'EXTCARTONATO.GEN' => 'monofase',
    'EXTFUSTELLATURA72X51' => 'monofase',
    'EXTPUNTOMETALLICOEST' => 'monofase',
    'EXTSTAMPA.OFFSET11.EST' => 'monofase',
    'EXTSTAMPABUSTE.EST' => 'monofase',
    'EXTSTAMPASECCO' => 'monofase',
    'EXTUVSPOTEST' => 'monofase',
    'EXTUVSPOTSPESSEST' => 'monofase',
    'DEKIA-semplice' => 'monofase',
    'STAMPASECCO' => 'monofase',
    'STAMPACALDO04' => 'monofase',
    'STAMPACALDOBR' => 'monofase',
        ];
    }
}