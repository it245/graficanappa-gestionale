<?php

declare(strict_types=1);

namespace App\Modules\Carta\Services;

use App\Models\Articolo;
use App\Models\MagazzinoArticolo;
use App\Modules\Carta\Enums\FamigliaCarta;
use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servizio di lettura sull'anagrafica articoli carta.
 *
 * Lavora sopra il modello Eloquent `App\Models\Articolo` (tabella `articoli`)
 * senza modificarne lo schema o i comportamenti.
 *
 * NB: il dominio "carta" qui è ricavato per parsing del cod_art Onda
 * (prefisso `02W.`). Articoli non-carta vengono ignorati.
 */
final class AnagraficaCartaService
{
    /**
     * TTL cache per le lookup distinct (formati, grammature, marche).
     * Le anagrafiche cambiano raramente: 1h è un buon compromesso.
     */
    public const CACHE_TTL = 3600;

    private const CACHE_PREFIX = 'carta:lookup:';

    /**
     * Cerca un articolo per cod_art Onda esatto (es. "02W.ALASKA.GC1.300.003").
     */
    public function cerca(string $codArt): ?Articolo
    {
        $codArt = strtoupper(trim($codArt));
        if ($codArt === '') {
            return null;
        }

        return Articolo::query()->where('cod_art', $codArt)->first();
    }

    /**
     * Ritorna gli articoli appartenenti a una famiglia (matching sul segmento TIPO).
     *
     * @return Collection<int, Articolo>
     */
    public function cercaPerFamiglia(FamigliaCarta $famiglia): Collection
    {
        // Pattern: 02W.<qualcosa>.<TIPO>.<grammatura>.<seq>
        // Filtra a livello DB, poi affina in PHP per evitare falsi positivi.
        $pattern = '02W.%.' . $famiglia->value . '.%';

        $candidates = Articolo::query()
            ->where('cod_art', 'LIKE', $pattern)
            ->get();

        return $candidates->filter(function (Articolo $a) use ($famiglia): bool {
            $vo = CodiceArticoloOnda::provaDaStringa((string) $a->cod_art);
            return $vo !== null && $vo->tipo === $famiglia->value;
        })->values();
    }

    /**
     * Ritorna tutti gli articoli che condividono lo stesso cod_art carta
     * della commessa indicata (utile per batching: stessa carta = batch).
     *
     * @return Collection<int, Articolo>
     */
    public function articoliCompatibiliCommessa(string $codCommessa): Collection
    {
        $codCommessa = trim($codCommessa);
        if ($codCommessa === '') {
            return new Collection();
        }

        // Recupera la carta della commessa target.
        $codiciCarta = Articolo::query()
            ->whereHas('ordine', function ($q) use ($codCommessa): void {
                $q->where('cod_commessa', $codCommessa);
            })
            ->whereNotNull('cod_carta')
            ->where('cod_carta', '!=', '')
            ->pluck('cod_carta')
            ->unique()
            ->values()
            ->all();

        if ($codiciCarta === []) {
            return new Collection();
        }

        return Articolo::query()
            ->whereIn('cod_carta', $codiciCarta)
            ->get();
    }

    /**
     * Decodifica il cod_art di un Articolo in VO, se possibile.
     */
    public function codiceOnda(Articolo $art): ?CodiceArticoloOnda
    {
        if (empty($art->cod_art)) {
            return null;
        }

        return CodiceArticoloOnda::provaDaStringa((string) $art->cod_art);
    }

    /**
     * Lista distinct dei formati presenti nell'anagrafica magazzino (cached 1h).
     *
     * Sostituisce la query `MagazzinoArticolo::whereNotNull('formato')->distinct()->pluck('formato')`
     * usata nei filtri lookup, evitando scan ripetuti sulla tabella.
     *
     * @return list<string>
     */
    public function formatiDisponibili(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'formati',
            self::CACHE_TTL,
            fn () => MagazzinoArticolo::query()
                ->whereNotNull('formato')
                ->where('formato', '!=', '')
                ->distinct()
                ->orderBy('formato')
                ->pluck('formato')
                ->all(),
        );
    }

    /**
     * Lista distinct delle grammature presenti nell'anagrafica magazzino (cached 1h).
     *
     * @return list<int>
     */
    public function grammatureDisponibili(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'grammature',
            self::CACHE_TTL,
            fn () => MagazzinoArticolo::query()
                ->whereNotNull('grammatura')
                ->distinct()
                ->orderBy('grammatura')
                ->pluck('grammatura')
                ->map(fn ($g) => (int) $g)
                ->all(),
        );
    }

    /**
     * Lista distinct delle marche/famiglie ricavate dal cod_art Onda (cached 1h).
     *
     * Itera solo gli articoli con prefisso `02W.` per limitare il parsing.
     *
     * @return list<string>
     */
    public function marcheDisponibili(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'marche',
            self::CACHE_TTL,
            function (): array {
                $codici = Articolo::query()
                    ->where('cod_art', 'LIKE', CodiceArticoloOnda::PREFISSO . '.%')
                    ->distinct()
                    ->pluck('cod_art');

                $marche = [];
                foreach ($codici as $cod) {
                    $vo = CodiceArticoloOnda::provaDaStringa((string) $cod);
                    if ($vo !== null) {
                        $marche[$vo->marca] = true;
                    }
                }

                $out = array_keys($marche);
                sort($out);

                return $out;
            },
        );
    }

    /**
     * Invalida la cache lookup (da chiamare quando l'anagrafica cambia).
     */
    public function invalidaCacheLookup(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'formati');
        Cache::forget(self::CACHE_PREFIX . 'grammature');
        Cache::forget(self::CACHE_PREFIX . 'marche');
    }
}
