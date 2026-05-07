<?php

declare(strict_types=1);

namespace App\Modules\Onda\Services;

use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Reparti\Rules\AssegnazioneFaseReparto;
use App\Modules\Reparti\Services\RepartoService;
use Carbon\Carbon;

/**
 * Sincronizza le fasi di lavorazione (`ordine_fasi`) da Onda al MES.
 *
 * Strangler Fig — STATO ATTUALE:
 * La creazione di {@see \App\Models\OrdineFase} è strettamente intrecciata
 * con l'upsert degli {@see \App\Models\Ordine} dentro un unico foreach loop
 * su `OndaErpInterface::getOrdiniDal()`: lo stato di dedup runtime
 * (`$dedupPerCommessa`, `$dedupQta`, `$fasiViste`) condivide chiavi che
 * fanno riferimento a `$ordine->id` non disponibile prima dell'upsert.
 *
 * Estrarre la "fasi-only" senza rompere bit-for-bit la logica richiede un
 * refactor più profondo (split del payload Onda in due passi: ordini-only
 * e poi un passe di sole fasi su ordini già upserted), che è fuori scope.
 *
 * In questa iterazione lasciamo questo servizio come ENTRY POINT logico
 * per fasi: delega a {@see OrdineSyncService::sync()} che già crea le fasi
 * e ritorna `fasi_create` nel result. Il valore aggiunto è:
 *  - dare ai caller un punto di accesso semanticamente corretto
 *    (`app(FaseSyncService::class)->sync($from)`);
 *  - aprire la porta a un'iterazione futura che spezzerà il loop in 2.
 *
 * Mappa fase→reparto delegata a {@see AssegnazioneFaseReparto::reparto()}
 * (regola pura, già usata dal modulo Reparti per il routing canonico).
 * Per la sync Onda, però, l'OrdineSyncService usa ancora i nomi granulari
 * via {@see RepartoService::mappaSlugToId()} perché la logica di dedup
 * dipende da pseudo-reparti ("fustella piana", "tagliacarte", ecc.) che
 * la regola pura collassa sui canonici (FUSTELLA, LEGATORIA).
 */
final class FaseSyncService
{
    public function __construct(
        private readonly OndaErpInterface $onda,
        private readonly OrdineSyncService $ordineSync,
        private readonly RepartoService $reparti,
    ) {}

    /**
     * Sincronizza le fasi delle commesse dal momento $dal in poi.
     *
     * @param Carbon|null $dal Data soglia. Se null, usa il default del legacy (2026-02-27).
     * @return array{create:int, errori:int}
     */
    public function sync(?Carbon $dal = null): array
    {
        // Delega a OrdineSyncService::sync() — vedi PHPDoc di classe per il razionale.
        $risultato = $this->ordineSync->sync($dal);

        return [
            'create'  => (int) ($risultato['fasi_create'] ?? 0),
            'errori'  => (int) ($risultato['errori'] ?? 0),
        ];
    }

    /**
     * Risolve il nome di reparto MES (canonico) per una fase Onda.
     *
     * Wrapper su {@see AssegnazioneFaseReparto::reparto()} che ritorna lo
     * slug del reparto canonico (NON i pseudo-reparti granulari).
     *
     * Use-case: caller esterni (es. ImportExcelTutto, magazzino) che vogliono
     * il routing canonico senza dover importare l'enum CodiceReparto.
     */
    public function repartoSlugPerFase(string $codiceFase): string
    {
        return AssegnazioneFaseReparto::reparto($codiceFase)->value;
    }

    /**
     * Espone l'adapter ai test.
     */
    public function adapter(): OndaErpInterface
    {
        return $this->onda;
    }
}
