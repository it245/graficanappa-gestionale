<?php

/**
 * Mossa 37: Sequenza fasi nel ciclo produttivo
 * Usato per determinare disponibilità fisica (predecessori) e posizione nel flusso
 *
 * Gruppi macro:
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
 * Seq 46  → Finitura / Numerazione
 * Seq 100 → Finestratura
 * Seq 110 → Piega-incolla
 * Seq 120 → Legatoria / Piega
 * Seq 130 → Brossura / Cartonato
 * Seq 500 → Numerazione / Perforazione
 * Seq 700 → Allestimento
 * Seq 800 → Esterno
 * Seq 999 → Spedizione (BRT)
 */

return [
    // === STAMPA OFFSET (seq 10) ===
    'STAMPA' => 10,
    'STAMPAXL106' => 10,
    'STAMPAXL106.1' => 10,
    'STAMPAXL106.2' => 10,
    'STAMPAXL106.3' => 10,
    'STAMPAXL106.4' => 10,
    'STAMPAXL106.5' => 10,
    'STAMPAXL106.6' => 10,
    'STAMPAXL106.7' => 10,
    'STAMPA.OFFSET11.EST' => 10,
    'STAMPA.ESTERNA' => 10,
    'STAMPABUSTE.EST' => 10,
    'AVVIAMENTISTAMPA.EST1.1' => 10,

    // === STAMPA DIGITALE (seq 11) ===
    'STAMPAINDIGO' => 11,
    'STAMPAINDIGOBN' => 11,
    'STAMPAINDIGOBIANCO' => 11,

    // === PLASTIFICAZIONE (seq 20) ===
    'PLAOPA1LATO' => 20,
    'PLAOPABV' => 20,
    'PLAPOLIESARG1LATO' => 20,
    'PLASAB1LATO' => 20,
    'PLASABBIA1LATO' => 20,
    'PLASOFTBV' => 20,
    'PLASOFTBVEST' => 20,
    'PLASOFTTOUCH1' => 20,

    // === STAMPA A CALDO (seq 30) ===
    'STAMPACALDOJOH' => 30,
    'STAMPACALDOJOH0,1' => 30,
    'STAMPACALDOJOH0,2' => 30,
    'STAMPACALDO04' => 30,
    'STAMPACALDOBR' => 30,
    'stampalaminaoro' => 30,
    'STAMPALAMINAORO' => 30,
    'STAMPACALDOJOHEST' => 30,
    'STAMPASECCO' => 30,
    'RILIEVOASECCOJOH' => 30,
    'CLICHESTAMPACALDO1' => 30,

    // === PLASTIFICAZIONE LUX (seq 31) ===
    'PLALUX1LATO' => 31,
    'PLALUXBV' => 31,

    // === FINITURA DIGITALE / UV / FOIL (seq 35) ===
    'FOIL.MGI.30M' => 35,
    'FOILMGI' => 35,
    'UVSERIGRAFICOEST' => 35,
    'UVSPOT.MGI.30M' => 35,
    'UVSPOT.MGI.9M' => 35,
    'UVSPOTEST' => 35,
    'UVSPOTSPESSEST' => 35,
    'DEKIA-Difficile' => 35,
    'DEKIA-semplice' => 35,

    // === TAGLIO (seq 37) ===
    'TAGLIACARTE' => 37,
    'TAGLIACARTE.IML' => 37,
    'TAGLIOINDIGO' => 37,

    // === ACCOPPIATURA (seq 38) ===
    'ACCOPPIATURA.FOG.33.48INT' => 38,

    // === RILIEVI BOBST (seq 39) ===
    'FUSTBOBSTRILIEVI' => 39,

    // === FUSTELLATURA (seq 40) ===
    'FUST.STARPACK.74X104' => 40,
    'FUSTBIML75X106' => 40,
    'FUSTbIML75X106' => 40,
    'FUSTBOBST75X106' => 40,
    'FUSTIML75X106' => 40,
    'FUSTELLATURA72X51' => 40,
    'FUSTSTELG33.44' => 40,
    'FUSTSTELP25.35' => 40,

    // === FINITURA / NUMERAZIONE (seq 46) ===
    'FIN01' => 46,
    'FIN03' => 46,
    'FIN04' => 46,
    'NUM33.44' => 46,

    // === FINESTRATURA (seq 100) ===
    'FINESTRATURA.INT' => 100,
    'FINESTRATURA.MANUALE' => 100,

    // === PIEGA-INCOLLA (seq 110) ===
    'PI01' => 110,
    'PI02' => 110,
    'PI03' => 110,

    // === LEGATORIA / PIEGA (seq 120) ===
    'CORDONATURAPETRATTO' => 120,
    'PIEGA2ANTECORDONE' => 120,
    'PIEGA2ANTESINGOLO' => 120,
    'PIEGA3ANTESINGOLO' => 120,
    'PIEGA4ANTESINGOLO' => 120,
    'PIEGA6ANTESINGOLO' => 120,
    'PIEGA8ANTESINGOLO' => 120,
    'PIEGA8TTAVO' => 120,
    'PIEGAMANUALE' => 120,
    'PUNTOMETALLICO' => 120,
    'PUNTOMETALLICOEST' => 120,
    'PUNTOMETALLICOESTCOPERT.' => 120,
    'PUNTOMETAMANUALE' => 120,
    'SPIRBLOCCOLIBROA3' => 120,
    'SPIRBLOCCOLIBROA4' => 120,
    'SPIRBLOCCOLIBROA5' => 120,
    'NUM.PROGR.' => 120,
    'PERF.BUC' => 120,

    // === BROSSURA / CARTONATO (seq 130) ===
    'BROSSCOPBANDELLAEST' => 130,
    'BROSSCOPEST' => 130,
    'BROSSFILOREFE/A4EST' => 130,
    'BROSSFILOREFE/A5EST' => 130,
    'BROSSFRESATA/A5EST' => 130,
    'BROSSFRESATA/A4EST' => 130,
    'BROSSPUR' => 130,
    'CARTONATO.GEN' => 130,

    // === ALLESTIMENTO (seq 700) ===
    'accopp+fust' => 700,
    'ACCOPPIATURA.FOGLI' => 700,
    'ACCOPP.FUST.INCOLL.FOGLI' => 700,
    'Allest.Manuale' => 700,
    'ALLEST.SHOPPER' => 700,
    'ALLEST.SHOPPER030' => 700,
    'ALL.COFANETTO.LEGOKART' => 700,
    'ALLESTIMENTO.ESPOSITORI' => 700,
    'APPL.BIADESIVO30' => 700,
    'APPL.CORDONCINO0,035' => 700,
    'appl.laccetto' => 700,
    'ARROT2ANGOLI' => 700,
    'ARROT4ANGOLI' => 700,
    'blocchi.manuale' => 700,
    'FASCETTATURA' => 700,
    'INCOLLAGGIO.PATTINA' => 700,
    'INCOLLAGGIOBLOCCHI' => 700,
    'LAVGEN' => 700,
    'SFUST' => 700,
    'SFUST.IML.FUSTELLATO' => 700,
    'ZUND' => 700,

    // === ESTERNO (seq 800) ===
    '4graph' => 800,
    'esterno' => 800,
    'ALL.COFANETTO.ISMAsrl' => 800,
    'PMDUPLO36COP' => 800,
    'PMDUPLO40AUTO' => 800,
    'EXTALL.COFANETTO.LEGOKART' => 800,
    'EXTAllest.Manuale' => 800,
    'EXTALLEST.SHOPPER' => 800,
    'EXTALLEST.SHOPPER024' => 800,
    'EXTALLESTIMENTO.ESPOSITOR' => 800,
    'EXTAPPL.CORDONCINO0,035' => 800,
    'EXTAVVIAMENTISTAMPA.EST1.' => 800,
    'EXTBROSSCOPEST' => 800,
    'EXTBROSSFILOREFE/A4EST' => 800,
    'EXTBROSSFILOREFE/A5EST' => 800,
    'EXTBROSSFRESATA/A4EST' => 800,
    'EXTBROSSFRESATA/A5EST' => 800,
    'EXTCARTONATO' => 800,
    'EXTCARTONATO.GEN' => 800,
    'EXTFUSTELLATURA72X51' => 800,
    'EXTPUNTOMETALLICOEST' => 800,
    'EXTPUNTOMETALLICOESTCOPER' => 800,
    'EXTSTAMPA.OFFSET11.EST' => 800,
    'EXTSTAMPABUSTE.EST' => 800,
    'EXTSTAMPASECCO' => 800,
    'EXTUVSPOTEST' => 800,
    'EXTUVSPOTSPESSEST' => 800,
    'est STAMPACALDOJOH' => 800,
    'est FUSTSTELG33.44' => 800,
    'est FUSTBOBST75X106' => 800,

    // === SPEDIZIONE (seq 999) ===
    'BRT' => 999,
    'BRT1' => 999,
    'brt1' => 999,
];
