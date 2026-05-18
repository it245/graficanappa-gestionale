<?php

namespace App\Services;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoUbicazione;
use Illuminate\Support\Facades\DB;

/**
 * Suggerisce ubicazione preferita per stoccaggio nuovo carico.
 *
 * Priorità (in ordine):
 * 1. Articolo ha ubicazione_preferita_id assegnata → usa quella
 * 2. Articolo simile (stessa categoria + grammatura ±20g + formato) → usa la SUA ubicazione preferita
 * 3. Ubicazione che già contiene questo articolo (consolida lotti)
 * 4. Ubicazione libera con stessa categoria
 * 5. Ubicazione generica (categoria null)
 *
 * Filtri: solo ubicazioni attiva=true e con spazio (se capacita_max set).
 */
class SuggerisciUbicazioneService
{
    /**
     * @param MagazzinoArticolo|string|null $articolo Articolo object o categoria string
     * @param float|null $quantitaCarico Quantità da stoccare (per check capacita)
     */
    public static function suggerisci($articolo, ?float $quantitaCarico = null): ?MagazzinoUbicazione
    {
        // 1) Ubicazione preferita esplicita
        if ($articolo instanceof MagazzinoArticolo && $articolo->ubicazione_preferita_id) {
            $u = MagazzinoUbicazione::find($articolo->ubicazione_preferita_id);
            if ($u && $u->attiva && self::haSpazio($u, $quantitaCarico)) return $u;
        }

        // 2) Cerca articoli simili (stessa categoria + grammatura/formato)
        if ($articolo instanceof MagazzinoArticolo) {
            $simile = self::trovaUbicazioneArticoliSimili($articolo, $quantitaCarico);
            if ($simile) return $simile;
        }

        $categoria = $articolo instanceof MagazzinoArticolo ? $articolo->categoria : $articolo;
        if (!$categoria || !in_array($categoria, MagazzinoArticolo::CATEGORIE)) return null;

        $articoloId = $articolo instanceof MagazzinoArticolo ? $articolo->id : null;

        // 3) Consolidamento: ubicazione che già contiene questo articolo
        if ($articoloId) {
            $consolidamento = MagazzinoUbicazione::query()
                ->where('attiva', true)
                ->whereHas('giacenze', fn($q) => $q->where('articolo_id', $articoloId)->where('quantita', '>', 0))
                ->orderByDesc('priorita')
                ->first();

            if ($consolidamento && self::haSpazio($consolidamento, $quantitaCarico)) return $consolidamento;
        }

        // 4) Ubicazione libera per categoria
        $candidati = MagazzinoUbicazione::query()
            ->where('attiva', true)
            ->where('categoria', $categoria)
            ->orderByDesc('priorita')
            ->orderBy('codice')
            ->get();

        foreach ($candidati as $u) {
            if (self::haSpazio($u, $quantitaCarico)) return $u;
        }

        // 5) Generiche (categoria null)
        $generici = MagazzinoUbicazione::query()
            ->where('attiva', true)
            ->whereNull('categoria')
            ->orderByDesc('priorita')
            ->orderBy('codice')
            ->get();

        foreach ($generici as $u) {
            if (self::haSpazio($u, $quantitaCarico)) return $u;
        }

        return null;
    }

    /**
     * Articoli simili = stessa categoria + grammatura ±20g + formato simile.
     * Ritorna ubicazione preferita di almeno 1 articolo simile (più frequente).
     */
    protected static function trovaUbicazioneArticoliSimili(MagazzinoArticolo $articolo, ?float $quantitaCarico): ?MagazzinoUbicazione
    {
        $q = MagazzinoArticolo::query()
            ->where('id', '!=', $articolo->id)
            ->where('attivo', true)
            ->where('categoria', $articolo->categoria)
            ->whereNotNull('ubicazione_preferita_id');

        // Match grammatura ±20g se specificata
        if ($articolo->grammatura) {
            $gMin = (int) $articolo->grammatura - 20;
            $gMax = (int) $articolo->grammatura + 20;
            $q->whereBetween('grammatura', [$gMin, $gMax]);
        }

        // Match formato esatto se specificato
        if ($articolo->formato) {
            $q->where('formato', $articolo->formato);
        }

        // Ubicazione più ricorrente tra articoli simili
        $ubicazioneId = $q->select('ubicazione_preferita_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('ubicazione_preferita_id')
            ->orderByDesc('cnt')
            ->value('ubicazione_preferita_id');

        if (!$ubicazioneId) return null;

        $u = MagazzinoUbicazione::find($ubicazioneId);
        if ($u && $u->attiva && self::haSpazio($u, $quantitaCarico)) return $u;

        return null;
    }

    /**
     * Lista top 3 alternative per UI scelta operatore.
     *
     * @return \Illuminate\Support\Collection<MagazzinoUbicazione>
     */
    public static function alternative($articolo, ?float $quantitaCarico = null, int $limit = 3): \Illuminate\Support\Collection
    {
        $categoria = $articolo instanceof MagazzinoArticolo ? $articolo->categoria : $articolo;
        if (!$categoria) return collect();

        return MagazzinoUbicazione::query()
            ->where('attiva', true)
            ->where(function ($q) use ($categoria) {
                $q->where('categoria', $categoria)->orWhereNull('categoria');
            })
            ->orderByDesc('priorita')
            ->orderBy('codice')
            ->get()
            ->filter(fn($u) => self::haSpazio($u, $quantitaCarico))
            ->take($limit)
            ->values();
    }

    private static function haSpazio(MagazzinoUbicazione $u, ?float $quantita): bool
    {
        if ($quantita === null || $u->capacita_max === null) return true;
        return $u->spazioRimanente() >= $quantita;
    }
}
