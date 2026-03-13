<?php

/**
 * Sequenza fasi di produzione per Mossa 37 — Scheduler
 * Mappa fase → sequenza nel flusso fisico ASSOLUTO
 * Una fase può partire SOLO quando TUTTE le fasi con sequenza inferiore
 * nella stessa commessa sono completate.
 *
 * Seq 10  → Stampa offset (XL106)
 * Seq 11  → Stampa digitale (Indigo)
 * Seq 20  → Plastificazione
 * Seq 30  → Stampa a caldo (JOH)
 * Seq 31  → Plastificazione lux
 * Seq 35  → Finitura digitale (MGI, UV spot, Dekia)
 * Seq 37  → Taglio
 * Seq 38  → Accoppiatura
 * Seq 39  → Rilievi (BOBST config RILIEVI)
 * Seq 40  → Fustellatura (BOBST config FUSTELLE, STEL)
 * Seq 100 → Finestratura
 * Seq 110 → Piega-incolla
 * Seq 120 → Legatoria
 * Seq 500 → Numerazione
 * Seq 700 → Allestimento / Lavorazioni esterne
 * Seq 999 → Spedizione (BRT)
 */

return [
    // Stampa offset
    'AVVIAMENTISTAMPA.EST1.1' => 1,
    'STAMPA' => 10, 'STAMPA.OFFSET11.EST' => 10, 'STAMPABUSTE.EST' => 10, 'STAMPA.ESTERNA' => 10,
    'STAMPAXL106' => 10, 'STAMPAXL106.1' => 10, 'STAMPAXL106.2' => 10, 'STAMPAXL106.3' => 10,
    'STAMPAXL106.4' => 10, 'STAMPAXL106.5' => 10, 'STAMPAXL106.6' => 10, 'STAMPAXL106.7' => 10,

    // Stampa digitale
    'STAMPAINDIGO' => 11, 'STAMPAINDIGOBN' => 11,

    // Plastificazione
    'PLAOPA1LATO' => 20, 'PLAOPABV' => 20, 'PLAPOLIESARG1LATO' => 20, 'PLASAB1LATO' => 20,
    'PLASABBIA1LATO' => 20, 'PLASOFTBV' => 20, 'PLASOFTBVEST' => 20, 'PLASOFTTOUCH1' => 20,

    // Stampa a caldo
    'STAMPACALDOJOH' => 30, 'STAMPACALDOJOH0,1' => 30, 'STAMPACALDOJOH0,2' => 30,
    'STAMPACALDO04' => 30, 'STAMPACALDOBR' => 30, 'STAMPALAMINAORO' => 30, 'CLICHESTAMPACALDO1' => 30,

    // Plastificazione lux
    'PLALUX1LATO' => 31, 'PLALUXBV' => 31,

    // Finitura digitale
    'FOIL.MGI.30M' => 35, 'FOILMGI' => 35, 'UVSERIGRAFICOEST' => 35, 'UVSPOT.MGI.30M' => 35,
    'UVSPOT.MGI.9M' => 35, 'UVSPOTEST' => 35, 'UVSPOTSPESSEST' => 35, 'DEKIA-semplice' => 35,

    // Taglio
    'TAGLIACARTE' => 37, 'TAGLIACARTE.IML' => 37, 'TAGLIOINDIGO' => 37,

    // Accoppiatura
    'ACCOPPIATURA.FOG.33.48INT' => 38,

    // Rilievi
    'FUSTBOBSTRILIEVI' => 39, 'RILIEVOASECCOJOH' => 39,

    // Fustellatura
    'FUST.STARPACK.74X104' => 40, 'FUSTBIML75X106' => 40, 'FUSTbIML75X106' => 40,
    'FUSTBOBST75X106' => 40, 'FUSTIML75X106' => 40, 'FUSTSTELG33.44' => 40, 'FUSTSTELP25.35' => 40,

    // Finestratura
    'FIN01' => 100, 'FIN03' => 100, 'FIN04' => 100, 'FINESTRATURA.MANUALE' => 100,

    // Piega-incolla
    'NUM33.44' => 110, 'PI01' => 110, 'PI02' => 110, 'PI03' => 110,

    // Legatoria
    'BROSSCOPBANDELLAEST' => 120, 'BROSSCOPEST' => 120, 'BROSSFILOREFE/A4EST' => 120,
    'BROSSFILOREFE/A5EST' => 120, 'BROSSPUR' => 120, 'CARTONATO.GEN' => 120,
    'CORDONATURAPETRATTO' => 120, 'DEKIA-Difficile' => 120,
    'PIEGA2ANTECORDONE' => 120, 'PIEGA2ANTESINGOLO' => 120, 'PIEGA3ANTESINGOLO' => 120,
    'PIEGA4ANTESINGOLO' => 120, 'PIEGA6ANTESINGOLO' => 120, 'PIEGA8ANTESINGOLO' => 120,
    'PIEGAMANUALE' => 120, 'PUNTOMETALLICO' => 120, 'PUNTOMETALLICOEST' => 120,
    'PUNTOMETALLICOESTCOPERT.' => 120, 'PUNTOMETAMANUALE' => 120,
    'SPIRBLOCCOLIBROA3' => 120, 'SPIRBLOCCOLIBROA4' => 120, 'SPIRBLOCCOLIBROA5' => 120,

    // Numerazione/perforazione
    'NUM.PROGR.' => 500, 'PERF.BUC' => 500,

    // Allestimento / lavorazioni esterne
    'accopp+fust' => 700, 'ACCOPPIATURA.FOGLI' => 700, 'ACCOPP.FUST.INCOLL.FOGLI' => 700,
    'Allest.Manuale' => 700, 'ALLEST.SHOPPER' => 700, 'ALLEST.SHOPPER030' => 700,
    'ALL.COFANETTO.LEGOKART' => 700, 'ALLESTIMENTO.ESPOSITORI' => 700,
    'APPL.BIADESIVO30' => 700, 'APPL.CORDONCINO0,035' => 700, 'appl.laccetto' => 700,
    'ARROT2ANGOLI' => 700, 'ARROT4ANGOLI' => 700, 'blocchi.manuale' => 700,
    'INCOLLAGGIO.PATTINA' => 700, 'INCOLLAGGIOBLOCCHI' => 700, 'LAVGEN' => 700,
    'SFUST' => 700, 'SFUST.IML.FUSTELLATO' => 700, 'ZUND' => 700,
    'FASCETTATURA' => 700,
    'EXTALLEST.SHOPPER' => 700, 'EXTALLESTIMENTO.ESPOSITOR' => 700,
    'EXTAllest.Manuale' => 700, 'EXTBROSSFILOREFE/A5EST' => 700,
    'EXTPUNTOMETALLICOEST' => 700, 'EXTPUNTOMETALLICOESTCOPER' => 700,
    'EXTUVSPOTEST' => 700, '4graph' => 700, 'esterno' => 700,
    'EXTACCOPPIATURA.FOG.33.48' => 700, 'EXTALLEST.SHOPPER024' => 700,
    'EXTBROSSFILOREFE/A4EST' => 700, 'EXTCARTONATO.GEN' => 700,
    'EXTSTAMPABUSTE.EST' => 700, 'PMDUPLO40AUTO' => 700,

    // Spedizione
    'BRT1' => 999, 'brt1' => 999, 'BRT' => 999,
];
