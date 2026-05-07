<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Events\ClienteSincronizzato;

/**
 * Sincronizza i clienti riflessi dagli ordini Onda.
 *
 * Stato attuale del MES: NON esiste tabella `clienti` dedicata —
 * il cliente vive come stringa `cliente_nome` su `ordini` (denormalizzato).
 * Questo servizio è quindi un thin wrapper: la "sync clienti" è già
 * implicita in {@see OrdineSyncService::sync()} (campo cliente_nome).
 *
 * Quando il MES introdurrà una tabella `clienti` separata (roadmap v2.x),
 * qui andrà la logica vera (upsert anagrafica, normalizzazione P.IVA, ecc.)
 * senza dover toccare i caller.
 *
 * Per ora espone solo un metodo di "ricognizione" (count clienti distinti)
 * per dashboard di monitoring.
 */
final class ClienteSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
    ) {}

    /**
     * Estrae nomi cliente distinti dagli ordini delle ultime N giornate.
     *
     * @param  int $giorni Finestra di scansione (default 30).
     * @return array{inseriti:int, aggiornati:int, errori:int}
     */
    public function sync(int $giorni = 30): array
    {
        $righe = $this->onda->getOrdiniDal(now()->subDays(abs($giorni)));

        $clienti = [];
        foreach ($righe as $r) {
            $nome = trim((string) ($r->ClienteNome ?? ''));
            if ($nome === '') continue;
            $clienti[$nome] = ($clienti[$nome] ?? 0) + 1;
        }

        // Dispatch eventi (no-op finché non c'è listener registrato)
        foreach (array_keys($clienti) as $nome) {
            event(new ClienteSincronizzato($nome));
        }

        // Senza tabella clienti dedicata: tutto è "aggiornato" per definizione
        // (i nomi vivono già su `ordini.cliente_nome`).
        return [
            'inseriti'   => 0,
            'aggiornati' => count($clienti),
            'errori'     => 0,
        ];
    }
}
