<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Modules\Onda\Contracts\OndaErpInterface;
use Carbon\Carbon;

/**
 * Sincronizza gli ordini di produzione (TBOrdini lato MES, ATTDocTeste TipoDocumento=2 lato Onda).
 *
 * Strangler Fig: estratto dal God Class {@see \App\Services\OndaSyncService::sincronizza()}.
 * In questa iterazione il servizio espone l'API pulita ({@see self::sync()}) ma
 * delega la business logic ancora al metodo statico legacy — l'I/O SQL Server
 * è stato però già spostato in {@see \App\Modules\Onda\Adapters\OndaErpAdapter}
 * (consumato dal legacy via app(OndaErpInterface::class)).
 *
 * Le iterazioni successive sposteranno qui il body completo (~900 righe di
 * pulizia duplicati, dedup per reparto, mapping STAMPA→XL106, propagazione fasi)
 * senza cambiare il contratto pubblico né lo schema DB.
 *
 * Vantaggi immediati:
 *  - i caller (cron `onda:sync`, controller MES) possono già migrare
 *  - i test unit possono mockare {@see OndaErpInterface} senza SQL Server
 *  - quando serve riscrivere la dedup logic, è isolata in 1 sola classe
 */
final class OrdineSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
    ) {}

    /**
     * Sincronizza ordini Onda dal momento $dal in poi.
     *
     * @param Carbon|null $dal Data soglia (default: 2026-02-27, allineato al legacy).
     *
     * @return array{
     *   inseriti:int,
     *   aggiornati:int,
     *   errori:int,
     *   ordini_creati:int,
     *   ordini_aggiornati:int,
     *   fasi_create:int,
     *   log_ordini_creati?:list<string>,
     *   log_ordini_aggiornati?:list<string>,
     *   log_fasi_create?:list<string>
     * }
     */
    public function sync(?Carbon $dal = null): array
    {
        // NOTA: $dal non ancora propagato al legacy (la finestra è hardcoded
        // a 2026-02-27 nella query originale di OndaSyncService::sincronizza).
        // Quando spostiamo il body qui, useremo $this->onda->getOrdiniDal($dal).
        unset($dal);

        $risultato = \App\Services\OndaSyncService::sincronizza();

        return [
            'inseriti'          => $risultato['ordini_creati'] ?? 0,
            'aggiornati'        => $risultato['ordini_aggiornati'] ?? 0,
            'errori'            => 0,
            'ordini_creati'     => $risultato['ordini_creati'] ?? 0,
            'ordini_aggiornati' => $risultato['ordini_aggiornati'] ?? 0,
            'fasi_create'       => $risultato['fasi_create'] ?? 0,
            'log_ordini_creati'     => $risultato['log_ordini_creati'] ?? [],
            'log_ordini_aggiornati' => $risultato['log_ordini_aggiornati'] ?? [],
            'log_fasi_create'       => $risultato['log_fasi_create'] ?? [],
        ];
    }

    /**
     * Espone l'adapter ai test (vedi OrdineSyncServiceTest).
     */
    public function adapter(): OndaErpInterface
    {
        return $this->onda;
    }
}
