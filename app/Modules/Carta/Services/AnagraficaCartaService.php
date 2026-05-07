<?php

declare(strict_types=1);

namespace App\Modules\Carta\Services;

use App\Models\Articolo;
use App\Modules\Carta\Enums\FamigliaCarta;
use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;
use Illuminate\Database\Eloquent\Collection;

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
}
