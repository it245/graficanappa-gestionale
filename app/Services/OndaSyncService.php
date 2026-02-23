<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\FaseStatoService;

class OndaSyncService
{
    public static function sincronizza(): array
    {
        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;

        $mappaReparti = self::getMappaReparti();
        $tipiFase = self::getTipoReparto();
        $mappaPriorita = config('fasi_priorita');
        $oggi = now()->format('Y-m-d');

        // 1. Query tutti gli ordini aperti (TipoDocumento=2, non chiusi)
        $righeOnda = DB::connection('onda')->select("
            SELECT
                t.CodCommessa,
                p.CodArt,
                p.OC_Descrizione,
                COALESCE(NULLIF(p.NCPRagioneSociale, ''), a.RagioneSociale) AS ClienteNome,
                p.QtaDaProdurre,
                p.DataPresConsegna,
                t.DataRegistrazione,
                carta.CodArt AS CodCarta,
                carta.Descrizione AS DescrizioneCarta,
                carta.Qta AS QtaCarta,
                carta.CodUnMis AS UMCarta,
                t.TotMerce,
                t.ncpcommentoprestampa AS NotePrestampa,
                t.ncprespocommessa AS Responsabile,
                t.OC_CommentoProduz AS CommentoProduzione,
                materiali.CostoMateriali,
                f.CodFase,
                f.CodMacchina,
                f.QtaDaLavorare,
                f.CodUnMis AS UMFase
            FROM ATTDocTeste t
            INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
            LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
            LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            OUTER APPLY (
                SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
                FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
                ORDER BY r.Sequenza
            ) carta
            OUTER APPLY (
                SELECT SUM(r2.Totale) AS CostoMateriali
                FROM PRDDocRighe r2 WHERE r2.IdDoc = p.IdDoc
            ) materiali
            WHERE t.TipoDocumento = '2'
              AND t.DataRegistrazione >= CAST('20260218' AS datetime)
        ");

        if (empty($righeOnda)) {
            return ['ordini_creati' => 0, 'ordini_aggiornati' => 0, 'fasi_create' => 0];
        }

        // 2. Raggruppa per (CodCommessa, CodArt, OC_Descrizione)
        $gruppi = collect($righeOnda)->groupBy(function ($riga) {
            return $riga->CodCommessa . '|' . $riga->CodArt . '|' . $riga->OC_Descrizione;
        });

        foreach ($gruppi as $chiave => $righe) {
            $prima = $righe->first();
            $commessa = trim($prima->CodCommessa ?? '');
            $codArt = trim($prima->CodArt ?? '');
            $descrizione = trim($prima->OC_Descrizione ?? '');

            if (!$commessa) continue;

            // 3. Upsert ordine (dedup per commessa + cod_art, la descrizione può cambiare)
            $ordine = Ordine::where('commessa', $commessa)
                ->where('cod_art', $codArt)
                ->first();

            $datiOrdine = [
                'cliente_nome'           => trim($prima->ClienteNome ?? ''),
                'data_prevista_consegna' => $prima->DataPresConsegna && date('Y', strtotime($prima->DataPresConsegna)) >= 2024 ? date('Y-m-d', strtotime($prima->DataPresConsegna)) : null,
                'qta_richiesta'          => $prima->QtaDaProdurre ?? 0,
                'cod_carta'              => trim($prima->CodCarta ?? ''),
                'carta'                  => trim($prima->DescrizioneCarta ?? ''),
                'qta_carta'              => $prima->QtaCarta ?? 0,
                'UM_carta'               => trim($prima->UMCarta ?? ''),
                'note_prestampa'         => trim($prima->NotePrestampa ?? ''),
                'responsabile'           => trim($prima->Responsabile ?? ''),
                'commento_produzione'    => trim($prima->CommentoProduzione ?? ''),
                'valore_ordine'          => ($prima->TotMerce ?? 0) > 0 ? (float) $prima->TotMerce : null,
                'costo_materiali'        => ($prima->CostoMateriali ?? 0) > 0 ? (float) $prima->CostoMateriali : null,
            ];

            if ($ordine) {
                $ordine->update(array_merge($datiOrdine, ['descrizione' => $descrizione]));
                $ordiniAggiornati++;
            } else {
                $ordine = Ordine::create(array_merge([
                    'commessa'    => $commessa,
                    'cod_art'     => $codArt,
                    'descrizione' => $descrizione,
                    'stato'       => 0,
                    'data_registrazione' => $prima->DataRegistrazione ? date('Y-m-d', strtotime($prima->DataRegistrazione)) : $oggi,
                ], $datiOrdine));
                $ordiniCreati++;
            }

            // 4. Fasi: raccogli fasi uniche per questo gruppo
            $fasiViste = [];
            foreach ($righe as $riga) {
                $faseNome = trim($riga->CodFase ?? '');
                if (!$faseNome) continue;

                // Rimappa STAMPA generico in base alla macchina assegnata in Onda
                if ($faseNome === 'STAMPA') {
                    $macchina = trim($riga->CodMacchina ?? '');
                    if (stripos($macchina, 'INDIGO') !== false) {
                        // HPINDIGOCO → STAMPAINDIGO, HPINDIGOBN → STAMPAINDIGOBN
                        if (stripos($macchina, 'BN') !== false || stripos($macchina, 'MONO') !== false) {
                            $faseNome = 'STAMPAINDIGOBN';
                        } else {
                            $faseNome = 'STAMPAINDIGO';
                        }
                    } elseif (preg_match('/XL106[.-]?(\d+)/i', $macchina, $m)) {
                        // XL106-1 → STAMPAXL106.1
                        $faseNome = 'STAMPAXL106.' . $m[1];
                    } elseif (stripos($macchina, 'XL106') !== false) {
                        $faseNome = 'STAMPAXL106';
                    }
                }

                $chiaveFase = $faseNome . '|' . ($riga->QtaDaLavorare ?? 0);
                if (isset($fasiViste[$chiaveFase])) continue;
                $fasiViste[$chiaveFase] = true;

                $repartoNome = $mappaReparti[$faseNome] ?? 'generico';
                $tipo = $tipiFase[$faseNome] ?? 'monofase';
                $prioritaFase = $mappaPriorita[$faseNome] ?? 500;

                $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
                $faseCatalogo = FasiCatalogo::firstOrCreate(
                    ['nome' => $faseNome],
                    ['reparto_id' => $reparto->id]
                );

                $dataFase = [
                    'ordine_id'        => $ordine->id,
                    'fase'             => $faseNome,
                    'fase_catalogo_id' => $faseCatalogo->id,
                    'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                    'um'               => trim($riga->UMFase ?? 'FG'),
                    'priorita'         => $prioritaFase,
                    'stato'            => 0,
                ];

                // Se la fase è stata rimappata da STAMPA generico, aggiorna la fase esistente
                $faseOriginaleOnda = trim($riga->CodFase ?? '');
                if ($faseOriginaleOnda === 'STAMPA' && $faseNome !== 'STAMPA') {
                    $faseStampaGenerica = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', 'STAMPA')
                        ->first();
                    if ($faseStampaGenerica) {
                        $faseStampaGenerica->update([
                            'fase'             => $faseNome,
                            'fase_catalogo_id' => $faseCatalogo->id,
                            'qta_fase'         => $riga->QtaDaLavorare ?? 0,
                            'um'               => trim($riga->UMFase ?? 'FG'),
                            'priorita'         => $prioritaFase,
                        ]);
                        $fasiCreate++;
                        continue;
                    }
                }

                // Logica dedup come OrdiniImport: monofase / max 2 fasi / multifase
                if ($tipo === 'monofase') {
                    // Max 1: aggiorna se esiste, crea se non esiste
                    $faseEsistente = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', $faseNome)
                        ->first();

                    if (!$faseEsistente) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                    }

                } elseif ($tipo === 'max 2 fasi') {
                    // Max 2: crea solo se ne esistono meno di 2
                    $count = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', $faseNome)
                        ->count();

                    if ($count < 2) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                    }

                } else {
                    // Multifase: crea solo se non esiste gia' con stessa fase per questo ordine
                    // (idempotente: non duplica a ogni sync)
                    $faseEsistente = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', $faseNome)
                        ->first();

                    if (!$faseEsistente) {
                        OrdineFase::create($dataFase);
                        $fasiCreate++;
                    }
                }
            }
        }

        // Ricalcola priorità e ore per tutte le fasi
        $controller = app(\App\Http\Controllers\DashboardOwnerController::class);
        $tutteLeFasi = OrdineFase::with('ordine')->get();
        foreach ($tutteLeFasi as $fase) {
            $controller->calcolaOreEPriorita($fase);
            $fase->save();
        }

        // Ricalcola stati per ogni ordine
        $ordineIds = OrdineFase::distinct()->pluck('ordine_id');
        foreach ($ordineIds as $ordineId) {
            FaseStatoService::ricalcolaStati($ordineId);
        }

        Log::info("Sync Onda completato: $ordiniCreati creati, $ordiniAggiornati aggiornati, $fasiCreate fasi");

        return [
            'ordini_creati'     => $ordiniCreati,
            'ordini_aggiornati' => $ordiniAggiornati,
            'fasi_create'       => $fasiCreate,
        ];
    }

    private static function getMappaReparti(): array
    {
        return [
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
            'FINESTRATURA.INT' => 'finestre',
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
            '4graph' => 'esterno',
            'stampalaminaoro' => 'stampa a caldo',
            'STAMPALAMINAORO' => 'stampa a caldo',
            'ALL.COFANETTO.ISMAsrl' => 'esterno',
            'PMDUPLO36COP' => 'esterno',
            'FINESTRATURA.MANUALE' => 'finestre',
            'STAMPACALDOJOHEST' => 'esterno',
            'BROSSFRESATA/A5EST' => 'esterno',
            'PIEGA6ANTESINGOLO' => 'legatoria',

            // Fasi con "est" nel nome o prefisso "EXT" → esterno
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

    private static function getTipoReparto(): array
    {
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
