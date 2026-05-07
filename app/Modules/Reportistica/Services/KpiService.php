<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\PrinectAttivita;
use App\Modules\Reportistica\Cache\ReportCache;
use App\Modules\Reportistica\Enums\TipoKpi;
use App\Modules\Reportistica\ValueObjects\KpiCard;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcolo KPI standard mostrati nelle dashboard admin/owner.
 *
 * Cache 5 min sul payload aggregato: la dashboard owner viene caricata
 * decine di volte al giorno e queste 6 query (di cui alcune con sub-filter)
 * pesavano ~200ms cumulativi (vedi reference_perf_audit.md).
 *
 * Dipendenze esterne ridotte: legge solo `ordini`, `ordine_fasi`,
 * `fase_operatore`, `prinect_attivita`. Nessuna mutazione.
 */
final class KpiService
{
    /**
     * @return Collection<int, KpiCard>
     */
    public function tutti(): Collection
    {
        $payload = ReportCache::remember(
            ReportCache::KEY_KPI,
            ReportCache::TTL_KPI,
            fn () => $this->calcola(),
        );

        return collect($payload)->map(fn (array $row) => new KpiCard(
            tipo:   TipoKpi::from($row['tipo']),
            label:  $row['label'],
            valore: $row['valore'],
            delta:  $row['delta'],
            trend:  $row['trend'],
            unita:  $row['unita'],
        ));
    }

    /**
     * Singolo KPI per chiave (utile per widget specifici).
     */
    public function unico(TipoKpi $tipo): ?KpiCard
    {
        return $this->tutti()->first(fn (KpiCard $c) => $c->tipo === $tipo);
    }

    /**
     * Calcolo "raw" (senza cache, senza mapping VO) — restituisce array
     * primitivi serializzabili per riposo nella cache.
     *
     * @return list<array{tipo:string,label:string,valore:int|float|string,delta:?float,trend:string,unita:string}>
     */
    private function calcola(): array
    {
        $oggi = Carbon::today();
        $da7gg = Carbon::now()->subDays(7);
        $da14gg = Carbon::now()->subDays(14);

        $cards = [];

        // 1) Commesse attive (almeno 1 fase incompleta)
        $commesseAttive = Ordine::query()
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('ordine_fasi')
                  ->whereColumn('ordine_fasi.ordine_id', 'ordini.id')
                  ->where('ordine_fasi.stato', '<', 3);
            })
            ->distinct('commessa')
            ->count('commessa');

        $cards[] = $this->card(TipoKpi::COMMESSE_ATTIVE, $commesseAttive);

        // 2) Fasi terminate oggi
        $fasiOggi = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)->where('ordine_fasi.stato', '!=', 5)
            ->whereDate('fase_operatore.data_fine', $oggi)
            ->count();

        $cards[] = $this->card(TipoKpi::FASI_OGGI, $fasiOggi);

        // 3) Ore lavorate ultimi 7gg + delta vs 7gg precedenti
        $oreSett   = $this->oreLavorate($da7gg, Carbon::now());
        $orePrev   = $this->oreLavorate($da14gg, $da7gg);
        $deltaSett = $orePrev > 0.0 ? round((($oreSett - $orePrev) / $orePrev) * 100.0, 1) : null;

        $cards[] = $this->card(TipoKpi::ORE_SETTIMANA, $oreSett, $deltaSett);

        // 4) Efficienza media ultimi 7gg (ore previste / ore effettive su fasi terminate)
        $efficienza = $this->efficienzaMedia($da7gg, Carbon::now());
        $cards[] = $this->card(TipoKpi::EFFICIENZA, $efficienza ?? 0);

        // 5) Tasso puntualità ultimi 30gg (clone della logica `cruscotto`)
        $tasso = $this->tassoPuntualita(Carbon::now()->subDays(30), Carbon::now());
        $cards[] = $this->card(TipoKpi::PUNTUALITA, $tasso);

        // 6) Scarto Prinect ultimi 7gg
        $prinect = PrinectAttivita::where('start_time', '>=', $da7gg)->get();
        $good    = (int) $prinect->sum('good_cycles');
        $waste   = (int) $prinect->sum('waste_cycles');
        $scarto  = ($good + $waste) > 0 ? round(($waste / ($good + $waste)) * 100.0, 1) : 0.0;

        $cards[] = $this->card(TipoKpi::SCARTO_PRINECT, $scarto);

        return $cards;
    }

    /**
     * @return array{tipo:string,label:string,valore:int|float|string,delta:?float,trend:string,unita:string}
     */
    private function card(TipoKpi $tipo, int|float|string $valore, ?float $delta = null): array
    {
        return [
            'tipo'   => $tipo->value,
            'label'  => $tipo->label(),
            'valore' => $valore,
            'delta'  => $delta,
            'trend'  => KpiCard::trendDa($delta),
            'unita'  => $tipo->unita(),
        ];
    }

    private function oreLavorate(Carbon $da, Carbon $a): float
    {
        $sec = (int) DB::table('fase_operatore')
            ->whereBetween('data_fine', [$da, $a])
            ->whereNotNull('data_inizio')
            ->whereNotNull('data_fine')
            ->select(DB::raw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, data_inizio, data_fine) - COALESCE(secondi_pausa, 0)), 0) as s'))
            ->value('s');
        return round(max($sec, 0) / 3600.0, 1);
    }

    private function efficienzaMedia(Carbon $da, Carbon $a): ?float
    {
        // Ore effettive da fase_operatore su fasi terminate del periodo
        $eff = $this->oreLavorate($da, $a);
        if ($eff <= 0.0) {
            return null;
        }
        // Ore previste: somma config/fasi_ore.php per ogni fase terminata.
        // Per efficienza siamo OK con un'approssimazione media (ore_avviamento + qta/copieh).
        $fasi = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)->where('ordine_fasi.stato', '!=', 5)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->select('ordine_fasi.fase', 'ordini.qta_carta')
            ->get();

        $fasiOre = config('fasi_ore', []);
        $orePrev = 0.0;
        foreach ($fasi as $f) {
            $info = $fasiOre[$f->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
            $copieh = $info['copieh'] ?: 1000;
            $orePrev += (float) $info['avviamento'] + ((float) ($f->qta_carta ?? 0) / $copieh);
        }
        return round(($orePrev / $eff) * 100.0, 1);
    }

    private function tassoPuntualita(Carbon $da, Carbon $a): float
    {
        $rows = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)->where('ordine_fasi.stato', '!=', 5)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->whereNotNull('ordini.data_prevista_consegna')
            ->select('ordini.commessa', 'ordini.data_prevista_consegna', DB::raw('MAX(fase_operatore.data_fine) as ultima_fine'))
            ->groupBy('ordini.commessa', 'ordini.data_prevista_consegna')
            ->get()
            ->filter(static function ($row) {
                return OrdineFase::whereHas('ordine', fn ($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() === 0;
            });

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $puntuali = $rows->filter(static function ($row) {
            return $row->ultima_fine
                && Carbon::parse($row->ultima_fine)->lte(Carbon::parse($row->data_prevista_consegna)->endOfDay());
        })->count();

        return round(($puntuali / $rows->count()) * 100.0, 1);
    }
}
