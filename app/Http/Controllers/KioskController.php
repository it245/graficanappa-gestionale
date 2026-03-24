<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Models\Reparto;
use App\Services\SolarLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KioskController extends Controller
{
    public function index()
    {
        $oggi = Carbon::today();

        // === KPI HEADER ===
        $completateOggi = OrdineFase::where('stato', 3)
            ->where(function ($q) use ($oggi) {
                $q->whereDate('data_fine', $oggi)
                  ->orWhereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi));
            })->count();

        $inCorso = OrdineFase::whereRaw("stato REGEXP '^[0-9]+$'")
            ->where('stato', 2)->count();

        $inCoda = OrdineFase::whereRaw("stato REGEXP '^[0-9]+$'")
            ->where('stato', 1)->count();

        $fustelleAttive = OrdineFase::whereHas('faseCatalogo', fn($q) =>
            $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', ['fustella piana', 'fustella cilindrica']))
        )->where('stato', '<', 3)->count();

        // === ZONA 1: MACCHINE IN CORSO ===
        $macchineConfig = [
            ['nome' => 'XL 106', 'reparti' => ['stampa offset'], 'fasi' => ['STAMPAXL106%', 'STAMPA XL%']],
            ['nome' => 'BOBST', 'reparti' => ['fustella piana']],
            ['nome' => 'JOH Caldo', 'reparti' => ['stampa a caldo']],
            ['nome' => 'Plastificatrice', 'reparti' => ['plastificazione']],
            ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla']],
            ['nome' => 'Finestratrice', 'reparti' => ['finestratura']],
            ['nome' => 'Fustella Cilindrica', 'reparti' => ['fustella cilindrica']],
            ['nome' => 'Canon V900', 'reparti' => ['digitale']],
            ['nome' => 'Tagliacarte', 'reparti' => ['tagliacarte']],
            ['nome' => 'Legatoria', 'reparti' => ['legatoria']],
        ];

        $macchine = [];
        foreach ($macchineConfig as $mc) {
            $query = OrdineFase::with(['ordine', 'operatori', 'faseCatalogo.reparto'])
                ->where('stato', 2)
                ->whereHas('faseCatalogo', fn($q) =>
                    $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', $mc['reparti']))
                );

            // Per stampa offset, filtra per nome fase
            if (isset($mc['fasi'])) {
                $query->where(function ($q) use ($mc) {
                    foreach ($mc['fasi'] as $f) {
                        $q->orWhere('fase', 'LIKE', $f);
                    }
                });
            }

            $fase = $query->orderBy('priorita')->first();

            if ($fase) {
                // Calcola ore previste e lavorate
                $fasiOre = config('fasi_ore');
                $infoFase = $fasiOre[$fase->fase] ?? null;
                $orePrev = 0;
                if ($infoFase) {
                    $qtaCarta = $fase->ordine->qta_carta ?? 0;
                    $copieh = $infoFase['copieh'] ?: 1000;
                    $orePrev = round($infoFase['avviamento'] + ($qtaCarta / $copieh), 1);
                }

                $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                if ($secPrinect > 0) {
                    $oreLav = round($secPrinect / 3600, 1);
                } else {
                    $totSecPausa = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
                    $di = $fase->operatori->whereNotNull('pivot.data_inizio')->sortBy('pivot.data_inizio')->first()?->pivot->data_inizio;
                    $secLordo = $di ? abs(now()->getTimestamp() - Carbon::parse($di)->getTimestamp()) : 0;
                    $oreLav = round(max($secLordo - $totSecPausa, 0) / 3600, 1);
                }

                $operatore = $fase->operatori->pluck('nome')->implode(', ') ?: '';

                $macchine[] = [
                    'nome' => $mc['nome'],
                    'attiva' => true,
                    'commessa' => $fase->ordine->commessa ?? '-',
                    'cliente' => $fase->ordine->cliente_nome ?? '-',
                    'descrizione' => \Illuminate\Support\Str::limit($fase->ordine->descrizione ?? '-', 50),
                    'ore_lav' => $oreLav,
                    'ore_prev' => $orePrev ?: 1,
                ];
            } else {
                $macchine[] = [
                    'nome' => $mc['nome'],
                    'attiva' => false,
                    'commessa' => '', 'cliente' => '', 'descrizione' => '',
                    'ore_lav' => 0, 'ore_prev' => 0,
                ];
            }
        }

        // === ZONA 2: PROSSIMI LAVORI ===
        $prossimi = [];
        foreach ($macchineConfig as $mc) {
            $fasiCoda = OrdineFase::with('ordine')
                ->whereIn('stato', [0, 1])
                ->whereHas('faseCatalogo', fn($q) =>
                    $q->whereHas('reparto', fn($q2) => $q2->whereIn('nome', $mc['reparti']))
                )
                ->orderByRaw("FIELD(stato, 1, 0)")
                ->orderBy('priorita')
                ->limit(3)
                ->get();

            if ($fasiCoda->isNotEmpty()) {
                $items = [];
                foreach ($fasiCoda as $f) {
                    $desc = \Illuminate\Support\Str::limit($f->ordine->descrizione ?? '-', 45);
                    $fustella = \App\Helpers\DescrizioneParser::parseFustella(
                        $f->ordine->descrizione ?? '', $f->ordine->cliente_nome ?? '', ''
                    );
                    $commessa = $f->ordine->commessa ?? '-';
                    $items[] = ['desc' => $desc, 'badge' => $commessa, 'badge_cls' => 'verde'];
                }
                $prossimi[strtoupper($mc['nome'])] = $items;
            }
        }

        // === ZONA 3: OBIETTIVO ===
        // Fasi completate nell'ultima ora
        $ultimaOra = OrdineFase::where('stato', 3)
            ->where('data_fine', '>=', now()->subHour())
            ->count();

        // Ore segnate oggi (somma pivot)
        $oreSegnate = DB::table('fase_operatore')
            ->whereDate('data_inizio', $oggi)
            ->whereNotNull('data_fine')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, data_inizio, data_fine) - COALESCE(secondi_pausa, 0)) as sec')
            ->value('sec');
        $oreSegnate = round(max($oreSegnate ?? 0, 0) / 3600, 1);

        // === ZONA 4: UTILIZZO MACCHINE ===
        $utilizzo = [];
        $repartiUtilizzo = [
            ['nome' => 'XL 106 (24h)', 'reparti' => ['stampa offset'], 'ore_disp' => 24],
            ['nome' => 'BOBST', 'reparti' => ['fustella piana'], 'ore_disp' => 16],
            ['nome' => 'JOH Caldo', 'reparti' => ['stampa a caldo'], 'ore_disp' => 16],
            ['nome' => 'Plastificatrice', 'reparti' => ['plastificazione'], 'ore_disp' => 16],
            ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla'], 'ore_disp' => 16],
            ['nome' => 'Finestratrice', 'reparti' => ['finestratura'], 'ore_disp' => 16],
            ['nome' => 'Fustella Cilindrica', 'reparti' => ['fustella cilindrica'], 'ore_disp' => 16],
            ['nome' => 'Canon V900', 'reparti' => ['digitale'], 'ore_disp' => 16],
            ['nome' => 'Tagliacarte', 'reparti' => ['tagliacarte'], 'ore_disp' => 16],
            ['nome' => 'Legatoria', 'reparti' => ['legatoria'], 'ore_disp' => 9],
        ];

        foreach ($repartiUtilizzo as $ru) {
            $repartoIds = Reparto::whereIn('nome', $ru['reparti'])->pluck('id');
            $secOggi = DB::table('fase_operatore')
                ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
                ->whereDate('fase_operatore.data_inizio', $oggi)
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, COALESCE(fase_operatore.data_fine, NOW())) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec')
                ->value('sec');

            $oreUsate = round(max($secOggi ?? 0, 0) / 3600, 1);
            $pct = $ru['ore_disp'] > 0 ? min(round(($oreUsate / $ru['ore_disp']) * 100), 100) : 0;

            $utilizzo[] = ['nome' => $ru['nome'], 'pct' => $pct];
        }

        // === SOLAR ===
        $solar = (new SolarLogService())->getDati();

        return view('kiosk', [
            'kpi' => [
                'completate' => $completateOggi,
                'in_corso' => $inCorso,
                'in_coda' => $inCoda,
                'fustelle' => $fustelleAttive,
            ],
            'macchine' => $macchine,
            'prossimi' => $prossimi,
            'obiettivo' => [
                'completate' => $completateOggi,
                'ultima_ora' => $ultimaOra,
                'ore' => $oreSegnate,
            ],
            'utilizzo' => $utilizzo,
            'solar' => $solar,
        ]);
    }
}
