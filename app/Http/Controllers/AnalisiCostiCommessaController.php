<?php

namespace App\Http\Controllers;

use App\Models\CommessaAltroCosto;
use App\Models\Ordine;
use App\Models\OrdineFase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisiCostiCommessaController extends Controller
{
    /**
     * Lista commesse con TUTTE le fasi terminate (stato 3 o 4).
     * Esclude commesse con anche solo una fase < 3.
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));

        $commesseTerminate = DB::table('ordini as o')
            ->join('ordine_fasi as orf', 'orf.ordine_id', '=', 'o.id')
            ->select(
                'o.commessa',
                DB::raw('MAX(o.cliente_nome) as cliente_nome'),
                DB::raw("GROUP_CONCAT(DISTINCT o.descrizione SEPARATOR ' · ') as descrizione"),
                DB::raw('MAX(o.data_prevista_consegna) as data_prevista_consegna')
            )
            ->groupBy('o.commessa')
            // Tutte le fasi devono essere >=3 (terminate o consegnate). Nessuna fase a 0/1/2.
            ->havingRaw("SUM(CASE WHEN orf.stato NOT REGEXP '^[0-9]+\$' OR CAST(orf.stato AS UNSIGNED) < 3 THEN 1 ELSE 0 END) = 0")
            ->havingRaw('COUNT(orf.id) > 0');

        if ($search !== '') {
            $commesseTerminate->where(function ($q) use ($search) {
                $q->where('o.commessa', 'LIKE', "%{$search}%")
                  ->orWhere('o.cliente_nome', 'LIKE', "%{$search}%")
                  ->orWhere('o.descrizione', 'LIKE', "%{$search}%");
            });
        }

        $righe = $commesseTerminate
            ->orderByDesc('data_prevista_consegna')
            ->paginate(50)
            ->appends(['q' => $search]);

        // Aggregati riepilogo per le commesse della pagina corrente
        $commesseList = collect($righe->items())->pluck('commessa')->all();
        $aggregates = [];
        $fogli = [];
        $altri = [];
        $oreReparti = [];

        if (!empty($commesseList)) {
            $aggregates = DB::table('ordini as o')
                ->join('ordine_fasi as orf', 'orf.ordine_id', '=', 'o.id')
                ->whereIn('o.commessa', $commesseList)
                ->whereNotNull('orf.data_inizio')
                ->select(
                    'o.commessa',
                    DB::raw('SUM(COALESCE(orf.tempo_avviamento_sec,0) + COALESCE(orf.tempo_esecuzione_sec,0)) as ore_sec'),
                    DB::raw('SUM(COALESCE(orf.scarti,0)) as scarti_tot'),
                    DB::raw('SUM(COALESCE(orf.tiro_cm_foil,0)) as tiri_tot'),
                    DB::raw('SUM(COALESCE(orf.inchiostro_g,0)) as inchiostro_tot')
                )
                ->groupBy('o.commessa')
                ->get()
                ->keyBy('commessa');

            // Ore per reparto (per tooltip/breakdown in lista)
            $oreReparti = DB::table('ordini as o')
                ->join('ordine_fasi as orf', 'orf.ordine_id', '=', 'o.id')
                ->leftJoin('fasi_catalogo as fc', 'fc.id', '=', 'orf.fase_catalogo_id')
                ->leftJoin('reparti as r', 'r.id', '=', 'fc.reparto_id')
                ->whereIn('o.commessa', $commesseList)
                ->whereNotNull('orf.data_inizio')
                ->select(
                    'o.commessa',
                    DB::raw('COALESCE(r.nome, "?") as reparto'),
                    DB::raw('SUM(COALESCE(orf.tempo_avviamento_sec,0) + COALESCE(orf.tempo_esecuzione_sec,0)) as sec')
                )
                ->groupBy('o.commessa', 'r.nome')
                ->get()
                ->groupBy('commessa');

            $fogli = DB::table('ordini as o')
                ->join('ordine_fasi as orf', 'orf.ordine_id', '=', 'o.id')
                ->leftJoin('fasi_catalogo as fc', 'fc.id', '=', 'orf.fase_catalogo_id')
                ->leftJoin('reparti as r', 'r.id', '=', 'fc.reparto_id')
                ->whereIn('o.commessa', $commesseList)
                ->whereIn(DB::raw('LOWER(r.nome)'), ['stampa offset', 'digitale'])
                ->select('o.commessa', DB::raw('MAX(orf.fogli_buoni) as fogli'))
                ->groupBy('o.commessa')
                ->get()
                ->keyBy('commessa');

            $altri = \App\Models\CommessaAltroCosto::whereIn('commessa', $commesseList)
                ->select('commessa', DB::raw('SUM(importo) as tot'))
                ->groupBy('commessa')
                ->get()
                ->keyBy('commessa');
        }

        return view('owner.costi.analisi_commesse_lista', compact('righe', 'search', 'aggregates', 'fogli', 'altri', 'oreReparti'));
    }

    /**
     * Dettaglio costi singola commessa.
     */
    public function show(string $commessa)
    {
        $ordini = Ordine::with(['fasi.faseCatalogo.reparto', 'fasi.operatori'])
            ->where('commessa', $commessa)
            ->get();

        if ($ordini->isEmpty()) {
            abort(404, "Commessa {$commessa} non trovata");
        }

        // 1. Ore lavorate per reparto (h:m, no €)
        $oreReparto = [];
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $repartoNome = $fase->faseCatalogo->reparto->nome ?? 'Sconosciuto';
                if (!isset($oreReparto[$repartoNome])) {
                    $oreReparto[$repartoNome] = 0;
                }
                $sec = (int) (($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0));
                if ($sec === 0) {
                    $sec = (int) $fase->operatori->sum(function ($op) {
                        if (!$op->pivot->data_inizio || !$op->pivot->data_fine) return 0;
                        $pausa = (int) ($op->pivot->secondi_pausa ?? 0);
                        $diff = Carbon::parse($op->pivot->data_fine)->getTimestamp()
                              - Carbon::parse($op->pivot->data_inizio)->getTimestamp();
                        return max($diff - $pausa, 0);
                    });
                }
                $oreReparto[$repartoNome] += $sec;
            }
        }
        $oreReparto = collect($oreReparto)->map(fn ($sec, $nome) => (object) [
            'reparto'   => $nome,
            'ore_hm'    => $this->formatHm($sec),
            'sec'       => $sec,
        ])->values()->sortByDesc('sec')->values();

        // 2. Fogli utilizzati: SOLO prima fase stampa offset/digitale (una volta sola)
        $faseStampa = null;
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $repartoSlug = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                if (str_contains($repartoSlug, 'stampa offset') || str_contains($repartoSlug, 'digitale')) {
                    if (!$faseStampa || ($fase->fogli_buoni ?? 0) > ($faseStampa->fogli_buoni ?? 0)) {
                        $faseStampa = $fase;
                    }
                }
            }
        }
        $fogliUtilizzati = $faseStampa ? (int) ($faseStampa->fogli_buoni ?? 0) : 0;

        // 3. Tiri (SUM tutte le fasi stampa a caldo) - da AP Excel sync futuro
        $tiriTotali = 0;
        // 4. Inchiostro (SUM tutte fasi stampa offset) - da AO Excel sync futuro
        $inchiostroTotale = 0;
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $tiriTotali += (float) ($fase->tiro_cm_foil ?? 0);
                $inchiostroTotale += (float) ($fase->inchiostro_g ?? 0);
            }
        }

        // 5. Scarti (operatore dichiarati, NO fogli_scarto Prinect)
        $scartiTotali = 0;
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $scartiTotali += (int) ($fase->scarti ?? 0);
            }
        }

        // 6. Altri costi
        $altriCosti = CommessaAltroCosto::where('commessa', $commessa)
            ->orderByDesc('data')->orderByDesc('id')->get();
        $totaleAltriCosti = (float) $altriCosti->sum('importo');

        // Lista fasi editable (tutte le fasi della commessa, ordinate per data_inizio)
        $fasiEditable = collect();
        foreach ($ordini as $ordine) {
            foreach ($ordine->fasi as $fase) {
                $fasiEditable->push((object) [
                    'id'            => $fase->id,
                    'fase'          => $fase->fase,
                    'reparto'       => $fase->faseCatalogo->reparto->nome ?? '-',
                    'descrizione'   => $ordine->descrizione,
                    'stato'         => $fase->stato,
                    'data_inizio'   => $fase->data_inizio,
                    'fogli_buoni'   => $fase->fogli_buoni,
                    'fogli_scarto'  => $fase->fogli_scarto,
                    'scarti'        => $fase->scarti,
                    'tiro_cm_foil'  => $fase->tiro_cm_foil ?? null,
                    'inchiostro_g'  => $fase->inchiostro_g ?? null,
                ]);
            }
        }
        $fasiEditable = $fasiEditable->sortBy('data_inizio')->values();

        // Info commessa
        $primoOrdine = $ordini->first();

        return view('owner.costi.analisi_commessa_dettaglio', [
            'fasiEditable'     => $fasiEditable,
            'commessa'         => $commessa,
            'cliente'          => $primoOrdine->cliente_nome ?? '-',
            'descrizione'      => $primoOrdine->descrizione ?? '-',
            'data_consegna'    => $primoOrdine->data_prevista_consegna,
            'qta_richiesta'    => $ordini->sum('qta_richiesta'),
            'oreReparto'       => $oreReparto,
            'fogliUtilizzati'  => $fogliUtilizzati,
            'faseStampaNome'   => $faseStampa ? ($faseStampa->faseCatalogo->reparto->nome ?? '-') : '-',
            'tiriTotali'       => $tiriTotali,
            'inchiostroTotale' => $inchiostroTotale,
            'scartiTotali'     => $scartiTotali,
            'altriCosti'       => $altriCosti,
            'totaleAltriCosti' => $totaleAltriCosti,
            'categorie'        => CommessaAltroCosto::CATEGORIE,
        ]);
    }

    public function storeAltroCosto(Request $request, string $commessa)
    {
        $data = $request->validate([
            'categoria'   => 'required|in:' . implode(',', array_keys(CommessaAltroCosto::CATEGORIE)),
            'descrizione' => 'nullable|string|max:500',
            'importo'     => 'nullable|numeric|min:0',
            'data'        => 'required|date',
        ]);

        CommessaAltroCosto::create([
            'commessa'    => $commessa,
            'categoria'   => $data['categoria'],
            'descrizione' => $data['descrizione'] ?? null,
            'importo'     => $data['importo'] ?? 0,
            'data'        => $data['data'],
            'autore'      => session('admin_nome') ?? session('operatore_nome') ?? 'admin',
        ]);

        return redirect()->route('owner.costi.analisi.show', $commessa)
            ->with('success', 'Costo aggiunto.');
    }

    /**
     * Aggiorna campi modificabili di una fase (tiro, inchiostro, scarti, fogli).
     */
    public function updateFase(Request $request, int $faseId)
    {
        $data = $request->validate([
            'tiro_cm_foil' => 'nullable|numeric|min:0',
            'inchiostro_g' => 'nullable|numeric|min:0',
            'fogli_scarto' => 'nullable|integer|min:0',
            'scarti'       => 'nullable|integer|min:0',
            'fogli_buoni'  => 'nullable|integer|min:0',
        ]);

        $fase = OrdineFase::findOrFail($faseId);
        $ordine = $fase->ordine;
        if (!$ordine) abort(404);

        foreach (['tiro_cm_foil', 'inchiostro_g', 'fogli_scarto', 'scarti', 'fogli_buoni'] as $f) {
            if (array_key_exists($f, $data)) {
                $fase->{$f} = $data[$f] === '' ? null : $data[$f];
            }
        }
        $fase->save();

        return redirect()->route('owner.costi.analisi.show', $ordine->commessa)
            ->with('success', "Fase {$fase->fase} aggiornata.");
    }

    public function deleteAltroCosto(int $id)
    {
        $costo = CommessaAltroCosto::findOrFail($id);
        $commessa = $costo->commessa;
        $costo->delete();
        return redirect()->route('owner.costi.analisi.show', $commessa)
            ->with('success', 'Costo eliminato.');
    }

    private function formatHm(int $sec): string
    {
        if ($sec <= 0) return '0m';
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        if ($h === 0) return "{$m}m";
        return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
    }
}
