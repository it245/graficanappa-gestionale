<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * % completamento aggregato a livello commessa.
 *
 * Estratto dalle map() ripetute in DashboardAdminController (cruscotto +
 * reportDirezione + scadenzeImminenti). Il calcolo è uniforme:
 *
 *   avanzamento = fasi_terminate (stato>=3, stato!=5) / fasi_totali * 100
 *
 * Per coerenza: lo stato 5 (esterno) NON conta come "terminata" perché
 * la commessa non è chiusa fino al rientro.
 */
final class ProgressoCommesseService
{
    /**
     * % avanzamento per singola commessa (0-100).
     */
    public function avanzamento(string $commessa): int
    {
        $fasiTot = OrdineFase::whereHas('ordine', fn ($q) => $q->where('commessa', $commessa))->count();
        if ($fasiTot === 0) {
            return 0;
        }
        $fasiDone = OrdineFase::whereHas('ordine', fn ($q) => $q->where('commessa', $commessa))
            ->where('stato', '>=', 3)
            ->where('stato', '!=', 5)
            ->count();
        return (int) round(($fasiDone / $fasiTot) * 100);
    }

    /**
     * True se TUTTE le fasi della commessa sono completate (e non esterne).
     */
    public function isCompletata(string $commessa): bool
    {
        return OrdineFase::whereHas('ordine', fn ($q) => $q->where('commessa', $commessa))
            ->where('stato', '<', 3)
            ->doesntExist();
    }

    /**
     * Mappa avanzamento per una lista di commesse — singola query batchata.
     *
     * @param  iterable<string> $commesse
     * @return Collection<string, int>
     */
    public function avanzamentoBatch(iterable $commesse): Collection
    {
        $list = collect($commesse)->unique()->values();
        if ($list->isEmpty()) {
            return collect();
        }

        // Una query per i totali...
        $tot = Ordine::whereIn('commessa', $list)
            ->join('ordine_fasi', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->select('ordini.commessa', DB::raw('COUNT(*) as n'))
            ->groupBy('ordini.commessa')
            ->pluck('n', 'commessa');

        // ...e una per i terminati.
        $done = Ordine::whereIn('commessa', $list)
            ->join('ordine_fasi', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('ordine_fasi.stato', '!=', 5)
            ->select('ordini.commessa', DB::raw('COUNT(*) as n'))
            ->groupBy('ordini.commessa')
            ->pluck('n', 'commessa');

        return $list->mapWithKeys(function ($c) use ($tot, $done) {
            $t = (int) ($tot[$c] ?? 0);
            $d = (int) ($done[$c] ?? 0);
            return [$c => $t > 0 ? (int) round(($d / $t) * 100) : 0];
        });
    }
}
