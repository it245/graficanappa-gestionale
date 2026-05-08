<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Ordine;
use App\Modules\Fasi\Services\FaseTransitionService;
use App\Modules\Fasi\Exceptions\FaseTransitionException;

class ProduzioneController extends Controller
{
    /**
     * Strangler Fig: la logica di transizione/pausa/ripresa è stata
     * estratta in App\Modules\Fasi\Services\FaseTransitionService.
     * Il controller resta sottile: validazione + call service + JSON response.
     */
    public function __construct(
        private readonly FaseTransitionService $fasi,
    ) {}

    public function index()
    {
        $fasiVisibili = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori'])->get();
        return view('produzione.index', compact('fasiVisibili'));
    }

    public function avviaFase(Request $request)
    {
        try {
            $fase = OrdineFase::with('operatori')->find($request->fase_id);
            if (!$fase) {
                return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            }

            $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');
            $terzista = $request->filled('terzista') ? (string) $request->input('terzista') : null;

            $result = $this->fasi->avvia($fase, $operatoreId ? (int) $operatoreId : null, $terzista);
            $fase = $result['fase'];

            $operatori = $fase->operatori->map(function ($op) {
                return [
                    'nome' => $op->nome,
                    'data_inizio' => $op->pivot->data_inizio
                        ? Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s')
                        : '-',
                ];
            });

            return response()->json([
                'success' => true,
                'nuovo_stato' => $this->statoLabel($fase->stato),
                'operatori' => $operatori,
            ]);
        } catch (\Exception $e) {
            \Log::error('avviaFase errore: ' . $e->getMessage(), ['fase_id' => $request->fase_id, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'messaggio' => 'Errore server. Riprova o contatta IT.'], 500);
        }
    }

    public function terminaFase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fase_id'       => 'required',
            'qta_prodotta'  => 'nullable|integer|min:0',
            'scarti'        => 'nullable|integer|min:0',
            'tiro'          => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Dati non validi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fase = OrdineFase::with('operatori', 'ordine')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        // Tiro obbligatorio per stampa a caldo (cm foil consumato)
        $fasiCaldo = ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO'];
        if (in_array(strtoupper($fase->fase ?? ''), $fasiCaldo, true)) {
            if ($request->tiro === null || $request->tiro === '' || (int) $request->tiro <= 0) {
                return response()->json([
                    'success' => false,
                    'messaggio' => 'Tiro (cm foil) obbligatorio per stampa a caldo.',
                ], 422);
            }
        }

        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');

        $payload = [
            'qta_prod' => $request->qta_prodotta,
            'scarti'   => $request->scarti,
            'tiro'     => $request->tiro,
            'rientro'  => $request->boolean('rientro'),
        ];

        try {
            $fase = $this->fasi->termina($fase, $operatoreId ? (int) $operatoreId : null, $payload);
        } catch (\Exception $e) {
            \Log::error('terminaFase errore: ' . $e->getMessage(), ['fase_id' => $fase->id, 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'messaggio' => 'Errore server. Riprova o contatta IT.'], 500);
        }

        // Caso "rientro" da esterno: stato = 1, no scarico carta
        if ($request->boolean('rientro')) {
            $fase->load('operatori');
            return response()->json([
                'success' => true,
                'nuovo_stato' => $this->statoLabel($fase->stato),
                'operatori' => $fase->operatori->map(fn ($op) => ['nome' => $op->nome]),
            ]);
        }

        // Conferma scarico carta SOLO per reparti che consumano carta
        $repNome = strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? '');
        $repartiCarta = ['stampa offset', 'digitale', 'tagliacarte'];
        $consumaCarta = in_array($repNome, $repartiCarta, true);
        $richiediScarico = $consumaCarta && !$fase->scarico_eseguito && !$fase->esterno;

        $payloadScarico = null;
        if ($richiediScarico) {
            $qtaProdotta = (int) ($fase->qta_prod ?? 0);
            $qtaTotale = $qtaProdotta + (int) ($request->scarti ?? 0);
            $payloadScarico = [
                'fase_id'             => $fase->id,
                'commessa'            => $fase->ordine?->commessa,
                'fase_nome'           => $fase->faseCatalogo?->nome_display ?? $fase->fase,
                'qta_prod'            => $qtaProdotta,
                'scarti'              => (int) ($request->scarti ?? 0),
                'qta_totale'          => $qtaTotale,
                'cod_carta'           => $fase->ordine?->cod_carta,
                'desc_carta'          => $fase->ordine?->carta,
                'qta_carta_richiesta' => $fase->ordine?->qta_carta,
            ];
            $fase->pending_scarico = true;
            $fase->save();
        }

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato),
            'operatori' => [], // nessuno rimane
            'richiedi_scarico' => $richiediScarico,
            'scarico' => $payloadScarico,
        ]);
    }

    public function pausaFase(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $motivo = $request->input('motivo');
        if (!$motivo) {
            return response()->json(['success' => false, 'messaggio' => 'Motivo della pausa mancante']);
        }

        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');
        $operatore = $operatoreId ? Operatore::find($operatoreId) : null;
        $qtaProdotta = $request->filled('qta_prodotta') ? (int) $request->input('qta_prodotta') : null;

        try {
            $this->fasi->pausa($fase, $motivo, $operatore, $qtaProdotta);
        } catch (FaseTransitionException $e) {
            return response()->json(['success' => false, 'messaggio' => $e->getMessage()], 422);
        }

        $fase->refresh();

        return response()->json([
            'success' => true,
            'nuovo_stato' => $fase->stato,
            'timeout' => $fase->timeout ? Carbon::parse($fase->timeout)->format('d/m/Y H:i:s') : null,
        ]);
    }

    public function riprendiFase(Request $request)
    {
        $fase = OrdineFase::with('operatori')->find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');
        $operatore = $operatoreId ? Operatore::find($operatoreId) : null;

        try {
            $this->fasi->riprendi($fase, $operatore);
        } catch (FaseTransitionException $e) {
            return response()->json(['success' => false, 'messaggio' => $e->getMessage()], 422);
        }

        $fase->load('operatori');

        $operatori = $fase->operatori->map(function ($op) {
            return [
                'nome' => $op->nome,
                'data_inizio' => $op->pivot->data_inizio
                    ? Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s')
                    : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'nuovo_stato' => $this->statoLabel($fase->stato),
            'operatori' => $operatori,
        ]);
    }

    public function aggiornaCampo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fase_id' => 'required|exists:ordine_fasi,id',
            'campo'   => 'required|string|in:qta_prod,note,scarti',
            'valore'  => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $fase = OrdineFase::find($request->fase_id);

        // Hardening security: assegnazione esplicita (no $fase->{$campo} dinamico).
        // Il validator già whitelista i campi, ma defense-in-depth previene
        // mass assignment se un domani qualcuno allarga la regola in:.
        $campo = $request->campo;
        $valore = $request->valore;

        switch ($campo) {
            case 'qta_prod':
                $fase->qta_prod = $valore;
                break;
            case 'note':
                $fase->note = $valore;
                break;
            case 'scarti':
                $fase->scarti = $valore;
                break;
            default:
                // Non dovrebbe mai capitare grazie al validator, ma blocchiamo comunque
                return response()->json(['success' => false, 'errors' => ['campo' => ['Campo non consentito']]], 422);
        }
        $fase->save();

        // Se aggiornato qta_prod, controlla se la fase è completata
        if ($campo === 'qta_prod') {
            \App\Services\FaseStatoService::controllaCompletamento($fase->id);
        }

        // Se note contengono "esterno" o "lavorato esternamente", segna come esterno
        if ($campo === 'note') {
            $esterno = preg_match('/\b(lavorato esternamente|esterno)\b/i', $valore ?? '');
            if ($esterno && !$fase->esterno) {
                $fase->esterno = true;
                $fase->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function aggiornaOrdineCampo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ordine_id' => 'required|exists:ordini,id',
            'campo'     => 'required|string|in:note_fasi_successive',
            'valore'    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ordine = Ordine::find($request->ordine_id);

        // Aggiorna su tutti gli ordini della stessa commessa
        Ordine::where('commessa', $ordine->commessa)
            ->update([$request->campo => $request->valore]);

        return response()->json(['success' => true]);
    }

    private function statoLabel($stato)
    {
        switch ($stato) {
            case 0: return 'Caricato';
            case 1: return 'Pronto';
            case 2: return 'Avviato';
            case 3: return 'Terminato';
            case 4: return 'Consegnato';
            case 5: return 'Esterno';
            default: return $stato;
        }
    }

    /**
     * Autocomplete ricerca articoli magazzino
     * GET /produzione/cerca-articolo?q=<testo>
     */
    public function cercaArticolo(Request $request)
    {
        $q = trim($request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $articoli = \App\Models\MagazzinoArticolo::where('attivo', true)
            ->where(function ($builder) use ($q) {
                $builder->where('codice', 'like', "%{$q}%")
                        ->orWhere('descrizione', 'like', "%{$q}%")
                        ->orWhere('categoria', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'codice' => $a->codice,
                    'descrizione' => $a->descrizione,
                    'um' => $a->um,
                    'giacenza' => $a->giacenzaTotale(),
                ];
            });

        return response()->json($articoli);
    }

    /**
     * Registra scarico carta collegato a una fase STAMPA.
     * POST /produzione/scarica-carta
     * Body: fase_id, articolo_id, quantita, lotto (opzionale)
     */
    public function scaricaCarta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fase_id' => 'required|exists:ordine_fasi,id',
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|numeric|min:1',
            'lotto' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fase = OrdineFase::with('ordine')->findOrFail($request->fase_id);
        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');

        try {
            $movimento = \App\Services\MagazzinoService::registraScarico([
                'articolo_id' => $request->articolo_id,
                'quantita' => $request->quantita,
                'lotto' => $request->lotto,
                'commessa' => $fase->ordine?->commessa,
                'fase' => $fase->fase,
                'operatore_id' => $operatoreId,
                'note' => "Scarico da fase #{$fase->id} — {$fase->fase}",
            ]);

            // Marca fase come scaricata (idempotente)
            $fase->scarico_eseguito = true;
            $fase->pending_scarico = false;
            $fase->articolo_carta_id = $request->articolo_id;
            $fase->qta_carta_prelevata = (int) $request->quantita;
            $fase->lotto_carta = $request->lotto;
            $fase->scarico_at = now();
            $fase->save();

            return response()->json([
                'success' => true,
                'movimento_id' => $movimento->id,
                'giacenza_dopo' => $movimento->giacenza_dopo,
                'messaggio' => "Scaricati {$request->quantita} collegati a commessa {$fase->ordine?->commessa}",
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Scarico carta fase errore', [
                'fase_id' => $fase->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'messaggio' => 'Errore durante lo scarico carta. Riprova o contatta IT.',
            ], 500);
        }
    }

    /**
     * Ritorna se una fase ha già uno scarico carta registrato e la quantità.
     * GET /produzione/stato-scarico/{faseId}
     */
    /**
     * Conferma scarico carta al termine fase (post-stato 3).
     * Body: fase_id, articolo_id (nullable), articolo_libero (nullable string),
     *       quantita_totale, lotto (nullable), salta (boolean: termina senza scaricare)
     */
    public function confermaScaricoFase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fase_id'         => 'required|exists:ordine_fasi,id',
            'salta'           => 'nullable|boolean',
            'articolo_id'     => 'nullable|exists:magazzino_articoli,id',
            'articolo_libero' => 'nullable|string|max:200',
            'quantita_totale' => 'nullable|numeric|min:0',
            'lotto'           => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fase = OrdineFase::with('ordine')->findOrFail($request->fase_id);

        // Se già scaricato, idempotente
        if ($fase->scarico_eseguito) {
            return response()->json(['success' => true, 'messaggio' => 'Scarico già eseguito', 'idempotent' => true]);
        }

        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');

        // Caso "salta": chiude la fase senza scaricare magazzino (es. carta gestita esternamente)
        if ($request->boolean('salta')) {
            $fase->scarico_eseguito = true;
            $fase->pending_scarico = false;
            $fase->scarico_at = now();
            $fase->save();
            return response()->json(['success' => true, 'skipped' => true, 'messaggio' => 'Scarico saltato']);
        }

        // Validazione: serve articolo_id O articolo_libero + quantita_totale > 0
        if (!$request->articolo_id && !$request->articolo_libero) {
            return response()->json(['success' => false, 'messaggio' => 'Seleziona articolo o inserisci descrizione libera'], 422);
        }
        if (!$request->quantita_totale || $request->quantita_totale <= 0) {
            return response()->json(['success' => false, 'messaggio' => 'Quantità deve essere > 0'], 422);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $fase, $operatoreId) {
                // Articolo standard → registra scarico magazzino
                if ($request->articolo_id) {
                    \App\Services\MagazzinoService::registraScarico([
                        'articolo_id'  => $request->articolo_id,
                        'quantita'     => (int) $request->quantita_totale,
                        'lotto'        => $request->lotto,
                        'commessa'     => $fase->ordine?->commessa,
                        'fase'         => $fase->fase,
                        'operatore_id' => $operatoreId,
                        'note'         => "Scarico automatico fine fase #{$fase->id}",
                    ]);
                    $fase->articolo_carta_id = $request->articolo_id;
                }
                // Articolo libero → solo log, no scarico magazzino
                $fase->qta_carta_prelevata = (int) $request->quantita_totale;
                $fase->lotto_carta = $request->lotto;
                $fase->scarico_eseguito = true;
                $fase->pending_scarico = false;
                $fase->scarico_at = now();
                $fase->save();
            });

            return response()->json([
                'success' => true,
                'messaggio' => 'Scarico carta confermato',
                'qta' => (int) $request->quantita_totale,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('confermaScaricoFase errore', [
                'fase_id' => $fase->id, 'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'messaggio' => 'Errore durante la conferma scarico. Riprova o contatta IT.'], 500);
        }
    }

    public function statoScarico($faseId)
    {
        $fase = OrdineFase::with('faseCatalogo.reparto', 'ordine')->findOrFail($faseId);
        $movimenti = \App\Models\MagazzinoMovimento::where('tipo', 'scarico')
            ->where('fase', $fase->fase)
            ->where('commessa', $fase->ordine->commessa ?? null)
            ->with('articolo:id,codice,descrizione,um')
            ->get();

        $qtaTotale = $movimenti->sum('quantita');

        // Info per trigger modal scarico inline (operatore inserisce scarti)
        $repNome = strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? '');
        $consumaCarta = in_array($repNome, ['stampa offset', 'digitale', 'tagliacarte'], true);
        $richiedi = $consumaCarta && (int) $fase->stato === 3
            && empty($fase->scarico_eseguito) && empty($fase->esterno);

        return response()->json([
            'fase_id' => $fase->id,
            'scaricato' => $movimenti->isNotEmpty(),
            'scarico_eseguito' => (bool) $fase->scarico_eseguito,
            'richiedi' => $richiedi,
            'commessa' => $fase->ordine?->commessa,
            'fase_nome' => $fase->faseCatalogo?->nome_display ?? $fase->fase,
            'cod_carta' => $fase->ordine?->cod_carta,
            'qta_prod' => (int) ($fase->qta_prod ?? 0),
            'scarti' => (int) ($fase->scarti ?? 0),
            'quantita_totale' => $qtaTotale,
            'movimenti' => $movimenti->map(fn($m) => [
                'id' => $m->id,
                'quantita' => $m->quantita,
                'articolo_codice' => $m->articolo?->codice,
                'articolo_desc' => $m->articolo?->descrizione,
                'um' => $m->articolo?->um ?? 'fg',
                'created_at' => $m->created_at?->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }
}
