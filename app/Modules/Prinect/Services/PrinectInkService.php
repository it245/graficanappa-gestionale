<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Services;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\ValueObjects\ConsumoInchiostro;
use App\Modules\Prinect\ValueObjects\WorkstepId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lettura + cache del consumo inchiostro per workstep.
 *
 * Wrappa l'endpoint /workstep/{id}/inkConsumption con cache 1h: l'API
 * Heidelberg è lenta (1-3s) e l'inchiostro non cambia retroattivamente
 * una volta che il workstep è COMPLETED.
 *
 * NB: in produzione il job CachePrinectInkJob (queue worker) chiama
 * questo service per pre-warm la cache. In dev/test si può chiamare
 * direttamente senza queue.
 */
final class PrinectInkService
{
    /** TTL cache: 1 ora (worksteps completati non cambiano più). */
    private const CACHE_TTL_SEC = 3600;

    public function __construct(
        private readonly PrinectApiInterface $api,
    ) {
    }

    /**
     * Recupera il ConsumoInchiostro di un workstep, usando cache se presente.
     *
     * @param  WorkstepId  $ws
     * @param  bool        $force  bypassa la cache (utile dopo workstep COMPLETED appena rilevato)
     */
    public function getConsumo(WorkstepId $ws, bool $force = false): ?ConsumoInchiostro
    {
        $key = $ws->cacheKey('prinect:ink');

        if ($force) {
            Cache::forget($key);
        }

        $raw = Cache::remember($key, self::CACHE_TTL_SEC, function () use ($ws): ?array {
            try {
                return $this->api->getWorkstepInk($ws->jobId, $ws->workstepId);
            } catch (\Throwable $e) {
                Log::error('Prinect sync', [
                    'op'       => 'getWorkstepInk',
                    'jobId'    => $ws->jobId,
                    'wsId'     => $ws->workstepId,
                    'error'    => $e->getMessage(),
                ]);
                return null;
            }
        });

        if (!is_array($raw)) {
            return null;
        }

        $consumi = $raw['inkConsumptions'] ?? [];
        if (!is_array($consumi)) {
            return null;
        }

        return ConsumoInchiostro::daApi($consumi);
    }

    /**
     * Invalida la cache inchiostro per un workstep (es. dopo terminazione).
     */
    public function invalidaCache(WorkstepId $ws): void
    {
        Cache::forget($ws->cacheKey('prinect:ink'));
    }

    /**
     * Pre-warm cache per più worksteps (chiamato dal queue job batch).
     *
     * @param  iterable<WorkstepId>  $worksteps
     * @return int  numero di consumi caricati con successo
     */
    public function preWarm(iterable $worksteps): int
    {
        $loaded = 0;
        foreach ($worksteps as $ws) {
            if ($this->getConsumo($ws) !== null) {
                $loaded++;
            }
        }
        return $loaded;
    }
}
