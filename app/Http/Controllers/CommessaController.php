<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Modules\Prinect\Contracts\PrinectApiInterface;

class CommessaController extends Controller
{
    public function show($commessa, PrinectApiInterface $prinect)
    {
        // Carica TUTTI gli ordini con questa commessa (fondente, latte, ecc.)
        // select() mirato: evita full SELECT * (50+ colonne)
        $ordini = Ordine::where('commessa', $commessa)
                        ->select(['id','commessa','descrizione','cliente_nome','cod_art','qta_richiesta','qta_carta','carta','cod_carta','um','data_registrazione','data_prevista_consegna','note_prestampa','responsabile','commento_produzione','colori','note_fasi_successive','ddt_vendita_id'])
                        ->with([
                            'fasi:id,ordine_id,fase,fase_catalogo_id,stato,priorita,priorita_manuale,esterno,qta_prod,scarti,fogli_buoni,fogli_scarto,tiro,note,data_inizio,data_fine,timeout,tempo_avviamento_sec,tempo_esecuzione_sec,tipo_consegna,scarico_eseguito',
                            'fasi.faseCatalogo:id,nome,reparto_id',
                            'fasi.faseCatalogo.reparto:id,nome',
                            'fasi.operatori:operatori.id,nome',
                            'cliche',
                        ])
                        ->get();

        if ($ordini->isEmpty()) {
            abort(404);
        }

        // Primo ordine per info generali (cliente, commessa)
        $ordine = $ordini->first();

        // Raccoglie TUTTE le fasi da tutti gli ordini
        $tutteLeFasi = $ordini->flatMap(function ($o) {
            return $o->fasi->map(function ($fase) use ($o) {
                $fase->ordine_descrizione = $o->descrizione;
                return $fase;
            });
        });

        // Sostituisci la relazione fasi con tutte le fasi combinate
        $ordine->setRelation('fasi', $tutteLeFasi);

        // Carica l'operatore corrente (serve per sapere il reparto)
        $operatore = \App\Models\Operatore::with('reparti')->find(
            request()->attributes->get('operatore_id') ?? session('operatore_id')
        );

        // Prossime commesse: seguono l'ordine della coda operatore (per reparto)
        // 1. Prende i reparti dell'operatore
        // 2. Cerca le fasi attive (stato 0/1/2) in quei reparti, ordinate per priorità
        // 3. Raggruppa per commessa (distinct) per evitare duplicati
        $repartoIds = ($operatore && $operatore->reparti) ? $operatore->reparti->pluck('id')->toArray() : [];

        if (!empty($repartoIds)) {
            // Query sulla tabella ordine_fasi JOIN ordini:
            // - filtra per reparto dell'operatore
            // - solo fasi con stato 0, 1, 2 (nella coda, non completate)
            // - escludi fasi esterne
            // - raggruppa per commessa → MIN(priorita) = priorità più alta della commessa
            // - ordina per quella priorità
            $commesseOrdinate = OrdineFase::query()
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
                ->whereIn('ordine_fasi.stato', [0, 1, 2])
                ->where(fn($q) => $q->where('ordine_fasi.esterno', false)->orWhereNull('ordine_fasi.esterno'))
                ->where('ordini.commessa', '!=', $commessa)
                ->select('ordini.commessa')
                ->selectRaw('MIN(ordine_fasi.priorita) as min_priorita')
                ->groupBy('ordini.commessa')
                ->orderBy('min_priorita')
                ->limit(10)
                ->pluck('commessa');
        } else {
            // Fallback (owner o senza reparto): ordina per data consegna
            $commesseOrdinate = Ordine::where('commessa', '!=', $commessa)
                ->whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
                ->orderBy('data_prevista_consegna')
                ->limit(10)
                ->pluck('commessa');
        }

        // Carica i modelli Ordine con conteggio fasi, poi ordina come la coda
        // unique('commessa'): una commessa può avere più ordini, ne mostriamo uno solo
        $prossime = Ordine::whereIn('commessa', $commesseOrdinate)
            ->withCount(['fasi', 'fasi as fasi_terminate_count' => fn($q) => $q->whereRaw("stato REGEXP '^[0-9]+$' AND stato >= 3 AND stato != 5")])
            ->get()
            ->unique('commessa')
            ->sortBy(fn($o) => $commesseOrdinate->search($o->commessa))
            ->values();


        // Anteprima foglio: solo flag presenza (immagine caricata lazy via endpoint dedicato)
        // Evita chiamata Prinect bloccante durante page load + base64 inline pesante.
        $preview = null;
        $jobId = ltrim(substr($commessa, 0, 7), '0');
        if ($jobId && is_numeric($jobId)) {
            $preview = ['exists' => true, 'url' => route('commesse.preview', ['commessa' => $commessa])];
        }

        // Fustella PDF (cerca in public/fustelle/ matchando il codice FS#### dalla descrizione/note)
        $fustellaCodice = null;
        foreach ($ordini as $o) {
            $notePre = $o->fasi->firstWhere('faseCatalogo.reparto.nome', 'prestampa')->note ?? '';
            $cod = \App\Helpers\DescrizioneParser::parseFustella($o->descrizione ?? '', $o->cliente_nome ?? '', $notePre);
            if ($cod) { $fustellaCodice = $cod; break; }
        }
        $fustella = \App\Helpers\FustellaResolver::resolve($fustellaCodice);

        return view('commesse.show', compact('ordine', 'ordini', 'prossime', 'operatore', 'preview', 'fustella'));
    }

    /**
     * Endpoint preview Prinect: ritorna binary image, cacheable.
     * Evita base64 inline bloccante nel HTML principale.
     */
    public function preview($commessa, PrinectApiInterface $prinect)
    {
        $jobId = ltrim(substr($commessa, 0, 7), '0');
        if (!$jobId || !is_numeric($jobId)) abort(404);

        $cacheKey = 'preview_commessa_' . $commessa;
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($prinect, $jobId) {
            try {
                $wsData = $prinect->getWorksteps((string) $jobId);
                foreach ($wsData['worksteps'] ?? [] as $ws) {
                    if (isset($ws['types']) && in_array('ConventionalPrinting', $ws['types'])) {
                        $prevData = $prinect->getWorkstepPreview((string) $jobId, (string) $ws['id']);
                        $previews = $prevData['previews'] ?? [];
                        if (!empty($previews)) return $previews[0];
                    }
                }
            } catch (\Exception $e) { /* prinect non disponibile */ }
            return null;
        });

        if (!$data || empty($data['data'])) abort(404);
        $bin = base64_decode($data['data']);
        return response($bin, 200, [
            'Content-Type' => $data['mimeType'] ?? 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
