<?php

return [
    /*
     | Data dalla quale il flusso "prelievo carta + scarti" è attivo
     | sulla dashboard operatore. Le fasi terminate prima di questa data
     | NON mostrano i link "Inserisci scarti / Inserisci prelievo"
     | (servono per non bombardare di link le fasi storiche pre-MES /
     | pre-rilascio def2.0).
     |
     | Aggiornare al momento del merge/release di def2.0 in produzione.
     */
    'release_def2_at' => env('MES_RELEASE_DEF2_AT', '2026-12-31 23:59:59'),
];
