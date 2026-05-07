<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Services;

use App\Models\Reparto;
use App\Modules\Reparti\Enums\CodiceReparto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service applicativo: query e lookup sui reparti.
 *
 * Cache "reparti_attivi" 1h per evitare round-trip DB ad ogni chiamata
 * (dashboard owner / operatore caricano la lista a OGNI richiesta).
 *
 * NB: la cache va invalidata manualmente dopo seeder o modifiche
 * tabella reparti — vedi {@see clearCache()}.
 */
final class RepartoService
{
    private const CACHE_KEY = 'reparti_attivi';
    private const CACHE_TTL = 3600; // 1 ora

    /**
     * Tutti i reparti Eloquent, cached.
     *
     * @return Collection<int, Reparto>
     */
    public function tutti(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function () {
            return Reparto::query()->orderBy('id')->get();
        });
    }

    /**
     * Reparto per slug (nome DB). Null se non trovato.
     */
    public function bySlug(string $slug): ?Reparto
    {
        return $this->tutti()->firstWhere('nome', $slug);
    }

    /**
     * Reparto per ID. Null se non trovato.
     */
    public function byId(int $id): ?Reparto
    {
        return $this->tutti()->firstWhere('id', $id);
    }

    /**
     * Risolve {@see CodiceReparto} → modello Eloquent (via id()).
     */
    public function byCodice(CodiceReparto $codice): ?Reparto
    {
        return $this->byId($codice->id());
    }

    /**
     * Lookup ID intero → CodiceReparto enum.
     */
    public function codiceFromId(int $id): ?CodiceReparto
    {
        return CodiceReparto::fromId($id);
    }

    /**
     * Invalida la cache. Chiamare dopo seeder o modifiche manuali.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
