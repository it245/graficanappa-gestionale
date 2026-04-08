<?php

/**
 * Configurazione macchine per lo scheduler Mossa 37
 *
 * turni: 'h24' = 0-24 lun-ven, 'standard' = 6-22 lun-ven
 * fasi: lista nomi fase che mappano su questa macchina
 * parametri: fase => [avviamento_ore, copie_ora]
 */

return [
    'macchine' => [
        'XL106' => [
            'nome' => 'Heidelberg XL 106 (16h)',
            'turni' => 'standard', // 6-22 lun-ven
            'fasi' => ['STAMPAXL106','STAMPAXL106.1','STAMPAXL106.2','STAMPAXL106.3',
                        'STAMPAXL106.4','STAMPAXL106.5','STAMPAXL106.6','STAMPAXL106.7','STAMPA'],
        ],
        'BOBST' => [
            'nome' => 'BOBST 75x106',
            'turni' => 'standard',
            'fasi' => ['FUSTBOBSTRILIEVI','FUSTBOBST75X106','FUSTIML75X106'],
            'config' => [ // 2 configurazioni, cambio = 1h
                'RILIEVI' => ['FUSTBOBSTRILIEVI'],
                'FUSTELLE' => ['FUSTBOBST75X106','FUSTIML75X106'],
            ],
            'cambio_config_ore' => 1.0,
        ],
        'STEL' => [
            'nome' => 'STEL G33/P25',
            'turni' => 'standard',
            'fasi' => ['FUSTSTELG33.44','FUSTSTELP25.35'],
        ],
        'JOH' => [
            'nome' => 'JOH Stampa a Caldo',
            'turni' => 'standard',
            'fasi' => ['STAMPACALDOJOH','STAMPALAMINAORO','CLICHESTAMPACALDO1',
                        'STAMPACALDO04','STAMPACALDOBR','STAMPACALDOJOH0,2'],
        ],
        'PLAST' => [
            'nome' => 'Plastificatrice',
            'turni' => 'standard',
            'fasi' => ['PLAOPA1LATO','PLALUX1LATO','PLASOFTTOUCH1','PLAPOLIESARG1LATO',
                        'PLAOPABV','PLASOFTBV','PLALUXBV'],
        ],
        'PIEGA' => [
            'nome' => 'Piegaincolla',
            'turni' => 'standard',
            'fasi' => ['PI01','PI02','PI03'],
            'config' => [
                'PI01' => ['PI01'],
                'PI02' => ['PI02'],
                'PI03' => ['PI03'],
            ],
            'cambio_config_ore' => 1.0,
        ],
        'FIN' => [
            'nome' => 'Finestratrice',
            'turni' => 'standard',
            'fasi' => ['FIN01','FIN03','FIN04','FINESTRATURA.MANUALE'],
        ],
        'INDIGO' => [
            'nome' => 'HP Indigo + MGI',
            'turni' => 'standard',
            'fasi' => ['STAMPAINDIGO','STAMPAINDIGOBN','FOIL.MGI.30M',
                        'UVSPOT.MGI.30M','UVSPOT.MGI.9M','DEKIA-semplice'],
        ],
        'TAGLIO' => [
            'nome' => 'Tagliacarte',
            'turni' => 'standard',
            'fasi' => ['TAGLIACARTE','TAGLIACARTE.IML','TAGLIOINDIGO'],
        ],
        'LEGAT' => [
            'nome' => 'Legatoria',
            'turni' => 'standard',
            'fasi' => ['INCOLLAGGIO.PATTINA','SFUST.IML.FUSTELLATO','SPIRBLOCCOLIBROA4',
                        'BROSSPUR','PUNTOMETALLICO','PERF.BUC','PIEGA2ANTESINGOLO',
                        'PIEGA2ANTECORDONE','PIEGA4ANTESINGOLO','PIEGA6ANTESINGOLO',
                        'APPL.CORDONCINO0,035','APPL.BIADESIVO30','LAVGEN','PIEGAMANUALE','FASCETTATURA'],
        ],
        'ZUND' => [
            'nome' => 'Zünd',
            'turni' => 'standard',
            'fasi' => ['ZUND'],
        ],
    ],

    // Parametri lavorazione: fase => [avviamento_ore, copie_ora]
    'parametri' => [
        'STAMPAXL106.1' => [0.65, 3900], 'STAMPAXL106.2' => [0.65, 3900], 'STAMPAXL106' => [0.65, 3900],
        'STAMPA' => [0.00, 1000],
        'STAMPAINDIGO' => [0.50, 1000], 'STAMPAINDIGOBN' => [0.50, 1000],
        'STAMPACALDOJOH' => [1.00, 2200], 'STAMPALAMINAORO' => [1.00, 2000], 'CLICHESTAMPACALDO1' => [0.50, 1000],
        'PLAOPA1LATO' => [0.50, 1500], 'PLALUX1LATO' => [0.50, 1500], 'PLASOFTTOUCH1' => [0.50, 1500],
        'PLAPOLIESARG1LATO' => [0.50, 1500], 'PLAOPABV' => [0.50, 1500], 'PLASOFTBV' => [0.50, 1500], 'PLALUXBV' => [0.50, 1500],
        'FUSTBOBSTRILIEVI' => [0.50, 3000], 'FUSTBOBST75X106' => [0.50, 3000], 'FUSTIML75X106' => [0.50, 3000],
        'FUSTSTELG33.44' => [0.50, 1500], 'FUSTSTELP25.35' => [0.50, 1500],
        'FIN01' => [0.50, 4000], 'FIN03' => [0.50, 4000], 'FINESTRATURA.MANUALE' => [0.50, 1000],
        'PI01' => [0.50, 6000], 'PI02' => [1.00, 5000], 'PI03' => [1.00, 4000],
        'FOIL.MGI.30M' => [0.50, 500], 'UVSPOT.MGI.30M' => [0.50, 500], 'UVSPOT.MGI.9M' => [0.50, 500],
        'DEKIA-semplice' => [0.30, 500], 'ZUND' => [0.30, 300],
        'TAGLIACARTE' => [0.30, 2000], 'TAGLIACARTE.IML' => [0.30, 2000], 'TAGLIOINDIGO' => [0.30, 1000],
        'INCOLLAGGIO.PATTINA' => [0.50, 1500], 'SFUST.IML.FUSTELLATO' => [0.50, 1000],
        'SPIRBLOCCOLIBROA4' => [0.50, 100], 'BROSSPUR' => [0.50, 300], 'PUNTOMETALLICO' => [0.50, 2000],
        'PERF.BUC' => [0.30, 3000], 'PIEGA2ANTESINGOLO' => [0.50, 2000], 'PIEGA2ANTECORDONE' => [0.50, 2000],
        'PIEGA4ANTESINGOLO' => [0.50, 1500], 'APPL.CORDONCINO0,035' => [0.50, 500],
        'APPL.BIADESIVO30' => [0.50, 1000], 'LAVGEN' => [0.30, 1000], 'PIEGAMANUALE' => [0.00, 200],
        'FASCETTATURA' => [0.30, 2000],
        'STAMPA.OFFSET11.EST' => [0.50, 3000], 'AVVIAMENTISTAMPA.EST1.1' => [0.50, 1000],
        'BRT1' => [0.00, 99999], 'BRT' => [0.00, 99999],
    ],

    // Default per fasi non in tabella
    'default_avviamento' => 0.5,
    'default_copie_ora' => 1500,

    // Setup
    'setup_pieno_min' => 25,
    'setup_ridotto_min' => 10,
    'soglia_batch_giorni' => 5,
];
