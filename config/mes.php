<?php

declare(strict_types=1);

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

    /*
     |--------------------------------------------------------------------------
     | Stampa — adapter di default
     |--------------------------------------------------------------------------
     | Quale integrazione StampaIntegrationInterface viene risolta dal
     | container quando un servizio richiede genericamente l'interfaccia.
     | Valori validi: 'prinect' | 'fiery'.
     */
    'stampa_default' => env('MES_STAMPA_DEFAULT', 'prinect'),

    /*
     |--------------------------------------------------------------------------
     | Notifiche — canali
     |--------------------------------------------------------------------------
     | canali_critici: usati per eventi bloccanti (sotto soglia, ritardi).
     | canali_default: usati per notifiche standard (avvio/termine fase).
     */
    'notifiche' => [
        'canali_critici' => ['telegram', 'email', 'browser_push'],
        'canali_default' => ['telegram'],
    ],

    /*
     |--------------------------------------------------------------------------
     | Scheduling — parametri Mossa 37
     |--------------------------------------------------------------------------
     */
    'scheduling' => [
        'soglia_urgenza_giorni' => 5,
        'cambio_setup_bobst_h' => 1.0,
        'cambio_setup_piegaincolla_h' => 1.0,
    ],

    /*
     |--------------------------------------------------------------------------
     | Audit — sink di persistenza
     |--------------------------------------------------------------------------
     | FQCN dell'adapter che implementa AuditSinkInterface.
     | Valori: App\Modules\Audit\Adapters\DatabaseAuditSink (default)
     |         App\Modules\Audit\Adapters\FileAuditSink     (JSONL append)
     |         App\Modules\Audit\Adapters\NullAuditSink     (no-op test)
     */
    'audit' => [
        'sink' => env('MES_AUDIT_SINK', \App\Modules\Audit\Adapters\DatabaseAuditSink::class),
    ],
];
