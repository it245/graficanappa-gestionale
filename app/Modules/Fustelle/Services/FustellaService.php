<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Services;

use App\Models\Fustella;
use App\Modules\Fustelle\Enums\StatoFustella;
use App\Modules\Fustelle\Enums\TipoFustella;
use App\Modules\Fustelle\ValueObjects\CodiceFustella;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * CRUD + lookup anagrafica fustelle.
 *
 * Lookup per codice è cached 1h (i codici sono stabili: la cache invalida
 * viene gestita su update via {@see self::aggiorna()}).
 */
final class FustellaService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function cercaPerCodice(string $codice): ?Fustella
    {
        $vo = CodiceFustella::provaDaStringa($codice);
        $key = $vo !== null ? (string) $vo : strtoupper(trim($codice));

        if ($key === '') {
            return null;
        }

        return Cache::remember(
            $this->cacheKey($key),
            self::CACHE_TTL_SECONDS,
            fn () => Fustella::query()->where('codice', $key)->first()
        );
    }

    /**
     * Crea una nuova fustella in stato PREPARAZIONE (default).
     *
     * @param array<string,mixed> $dati
     */
    public function crea(CodiceFustella $codice, TipoFustella $tipo, array $dati = []): Fustella
    {
        $f = new Fustella();
        $f->codice = (string) $codice;
        $f->tipo = $tipo;
        $f->stato = StatoFustella::PREPARAZIONE;
        $f->dimensione_mm_x = $dati['dimensione_mm_x'] ?? null;
        $f->dimensione_mm_y = $dati['dimensione_mm_y'] ?? null;
        $f->spessore_mm = $dati['spessore_mm'] ?? null;
        $f->posizione_magazzino = $dati['posizione_magazzino'] ?? null;
        $f->note = $dati['note'] ?? null;
        $f->save();

        $this->invalidaCache((string) $codice);
        return $f;
    }

    /**
     * @param array<string,mixed> $modifiche
     */
    public function aggiorna(Fustella $fustella, array $modifiche): Fustella
    {
        $fustella->fill($modifiche);
        $fustella->save();

        $this->invalidaCache((string) $fustella->codice);
        return $fustella->refresh();
    }

    /**
     * Cambia stato rispettando la state-machine di {@see StatoFustella}.
     *
     * @throws \DomainException se la transizione non è ammessa.
     */
    public function cambiaStato(Fustella $fustella, StatoFustella $nuovo): Fustella
    {
        $corrente = $fustella->stato;
        if (!$corrente->puoTransitareA($nuovo)) {
            throw new \DomainException(
                "Transizione stato non ammessa: {$corrente->value} → {$nuovo->value}"
            );
        }

        $fustella->stato = $nuovo;
        $fustella->save();

        $this->invalidaCache((string) $fustella->codice);
        return $fustella;
    }

    /**
     * @return Collection<int, Fustella>
     */
    public function disponibili(): Collection
    {
        return Fustella::query()
            ->where('stato', StatoFustella::PRONTA->value)
            ->orderBy('codice')
            ->get();
    }

    /**
     * @return Collection<int, Fustella>
     */
    public function perTipo(TipoFustella $tipo): Collection
    {
        return Fustella::query()
            ->where('tipo', $tipo->value)
            ->orderBy('codice')
            ->get();
    }

    private function cacheKey(string $codice): string
    {
        return 'fustella.codice.' . $codice;
    }

    private function invalidaCache(string $codice): void
    {
        Cache::forget($this->cacheKey(strtoupper(trim($codice))));
    }
}
