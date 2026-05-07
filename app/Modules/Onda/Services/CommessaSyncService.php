<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Modules\Onda\Contracts\OndaErpInterface;

/**
 * Sincronizza una singola commessa da Onda, senza filtro data.
 *
 * Use-case: commessa "vecchia" che la sync incrementale non prende
 * (es. riapertura di una commessa archiviata, fix manuale).
 *
 * Strangler Fig: estratto da {@see \App\Services\OndaSyncService::sincronizzaSingolaCommessa()}.
 * Body legacy mantenuto per backward compat — l'I/O SQL è già stato
 * spostato in {@see \App\Modules\Onda\Adapters\OndaErpAdapter}.
 */
final class CommessaSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
    ) {}

    /**
     * Sync di una singola commessa Onda.
     *
     * @return array{
     *   trovata:bool,
     *   ordini_creati?:int,
     *   ordini_aggiornati?:int,
     *   fasi_create?:int,
     *   fasi?:list<string>,
     *   messaggio:string
     * }
     */
    public function sync(string $codCommessa): array
    {
        return \App\Services\OndaSyncService::sincronizzaSingolaCommessa($codCommessa);
    }

    public function adapter(): OndaErpInterface
    {
        return $this->onda;
    }
}
