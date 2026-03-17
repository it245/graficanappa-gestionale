<?php

/**
 * Configurazione orari turni.
 * T = turno unico, 1/2/3 = turni a rotazione.
 * Per override individuali, usare ora_inizio/ora_fine nella tabella turni.
 */
return [
    'orari' => [
        'T' => ['inizio' => '08:00', 'fine' => '17:00'],
        '1' => ['inizio' => '06:00', 'fine' => '14:00'],
        '2' => ['inizio' => '14:00', 'fine' => '22:00'],
        '3' => ['inizio' => '22:00', 'fine' => '06:00'],
    ],

    // Tolleranza ritardo in minuti
    'tolleranza_minuti' => 15,
];
