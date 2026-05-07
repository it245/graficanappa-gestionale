<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Services;

use App\Models\OrdineFase;
use App\Modules\Reparti\Services\RepartoService;
use App\Modules\Reportistica\Cache\ReportCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Panoramica reparti owner: per ogni reparto le commesse con fasi attive
 * (stato 2 = AVVIATA), raggruppate.
 *
 * Estratto da {@see \App\Http\Controllers\DashboardOwnerController::repartiOverview}.
 *
 * Cache 10 min sul payload aggregato — il template owner.reparti_overview
 * lo accetta tale e quale tramite `data` compact.
 */
final class PanoramicaRepartiService
{
    /** @var list<string> ordine ciclo produttivo (stamping → finishing → ship) */
    private const ORDINE_REPARTI = [
        'prestampa', 'stampa offset', 'digitale', 'stampa a caldo',
        'plastificazione', 'fustella', 'fustella piana', 'fustella cilindrica',
        'tagliacarte', 'finestratura', 'piegaincolla', 'legatoria',
        'finitura digitale', 'generico', 'produzione', 'magazzino',
        'spedizione', 'esterno',
    ];

    public function __construct(
        private readonly RepartoService $reparti,
    ) {}

    /**
     * Lista reparti con almeno 1 commessa attiva, ordinata sul ciclo produttivo.
     *
     * @return Collection<int, object{reparto:object, commesse:Collection, totale:int}>
     */
    public function overview(): Collection
    {
        return ReportCache::remember(
            ReportCache::KEY_PANORAMICA,
            ReportCache::TTL_PANORAMICA,
            fn () => $this->calcola()->values(),
        );
    }

    /**
     * Aggregato "ore + count fasi" per reparto, usato in cruscotto direzione.
     *
     * @return Collection<int, object{nome:string, attesa:int, in_corso:int, completate:int, ore:float}>
     */
    public function caricoEOre(): Collection
    {
        return $this->reparti->tutti()->map(function ($rep) {
            $fasi = DB::table('ordine_fasi')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->select('ordine_fasi.stato')
                ->get();

            $oreSec = (int) DB::table('fase_operatore')
                ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->whereNotNull('fase_operatore.data_inizio')
                ->whereNotNull('fase_operatore.data_fine')
                ->select(DB::raw('COALESCE(SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)), 0) as s'))
                ->value('s');

            return (object) [
                'nome'       => $rep->nome,
                'attesa'     => $fasi->whereIn('stato', [0, 1])->count(),
                'in_corso'   => $fasi->where('stato', 2)->count(),
                'completate' => $fasi->where('stato', '>=', 3)->where('stato', '!=', 5)->count(),
                'ore'        => round(max($oreSec, 0) / 3600.0, 1),
            ];
        })->filter(fn ($r) => ($r->attesa + $r->in_corso + $r->completate) > 0)
          ->values();
    }

    private function calcola(): Collection
    {
        $fasiInfo = config('fasi_ore', []);

        $reparti = $this->reparti->tutti()->sortBy(function ($r) {
            $pos = array_search(strtolower($r->nome), self::ORDINE_REPARTI, true);
            return $pos !== false ? $pos : 999;
        });

        $data = [];
        foreach ($reparti as $reparto) {
            $fasi = OrdineFase::query()
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->where('fasi_catalogo.reparto_id', $reparto->id)
                ->where('ordine_fasi.stato', 2)
                ->where(fn ($q) => $q->where('ordine_fasi.esterno', false)->orWhereNull('ordine_fasi.esterno'))
                ->whereNull('ordine_fasi.deleted_at')
                ->select([
                    'ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione',
                    'ordini.data_prevista_consegna', 'ordini.qta_richiesta',
                    'ordine_fasi.id as fase_id', 'ordine_fasi.fase',
                    'ordine_fasi.stato as fase_stato', 'ordine_fasi.priorita',
                    'ordine_fasi.priorita_manuale', 'ordine_fasi.qta_prod',
                    'ordine_fasi.operatore_id', 'fasi_catalogo.nome as fase_catalogo_nome',
                ])
                ->orderBy('ordine_fasi.priorita')
                ->get();

            $commesse = $fasi->groupBy('commessa')->map(function ($group) use ($fasiInfo) {
                $first = $group->first();
                $stati = $group->pluck('fase_stato');
                $orePreviste = $group->sum(function ($f) use ($fasiInfo) {
                    $info = $fasiInfo[$f->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
                    $copieh = $info['copieh'] ?: 1000;
                    return $info['avviamento'] + (($f->qta_richiesta ?: 0) / $copieh);
                });
                return (object) [
                    'commessa'    => $first->commessa,
                    'cliente'     => $first->cliente_nome ?: '-',
                    'descrizione' => $first->descrizione ?: '-',
                    'consegna'    => $first->data_prevista_consegna,
                    'qta'         => $first->qta_richiesta,
                    'priorita'    => $group->min('priorita'),
                    'n_fasi'      => $group->count(),
                    'n_inizio'    => $stati->filter(fn ($s) => $s == 1)->count(),
                    'n_terminato' => $stati->filter(fn ($s) => $s == 2)->count(),
                    'n_attesa'    => $stati->filter(fn ($s) => $s == 0)->count(),
                    'fasi'        => $group->pluck('fase_catalogo_nome')->unique()->values()->all(),
                    'ore_previste' => round($orePreviste, 1),
                ];
            })->sortBy('priorita')->values();

            $data[] = (object) [
                'reparto'  => $reparto,
                'commesse' => $commesse,
                'totale'   => $commesse->count(),
            ];
        }

        return collect($data)->filter(fn ($r) => $r->totale > 0)->values();
    }
}
