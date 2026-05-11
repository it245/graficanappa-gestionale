<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OwnerAggiornaCampoRequest;
use App\Http\Requests\OwnerAggiungiRigaRequest;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\Reparto;
use App\Models\FasiCatalogo;
use App\Models\DdtSpedizione;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Constants\StatoFase;
use App\Helpers\DescrizioneParser;
use App\Services\FaseStatoService;
use App\Services\OndaSyncService;
use App\Http\Services\PrinectSyncService;
use App\Modules\Onda\Services\OrdineSyncService;
use App\Modules\Spedizione\Services\DdtSyncService;
use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Http\Services\ExcelSyncService;
use App\Modules\Fasi\Services\FaseTransitionService;
use App\Modules\Fasi\Exceptions\FaseTransitionException;
use App\Modules\Commessa\Services\CommessaService;
use App\Modules\Commessa\Services\ProgressoService;
use App\Modules\Reparti\Services\RepartoService;
use App\Modules\Reparti\Services\CapacitaService;
use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Macchine\MacchinaRegistry;
use App\Modules\Fustelle\Services\NoteFustellaService;
use App\Modules\Notifiche\Services\NotificaService;
use App\Modules\Notifiche\ValueObjects\Notifica;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\Enums\PrioritaNotifica;
use App\Modules\Reportistica\Services\PanoramicaRepartiService;
use App\Modules\Reportistica\Services\ReportOreService;
use App\Modules\Reportistica\Services\FustelleReportService;
use App\Modules\Reportistica\Services\EsterneReportService;

class DashboardOwnerController extends Controller
{
    /**
     * Strangler Fig: la logica di mutazione fasi/commesse delega ai
     * moduli App\Modules\Fasi, App\Modules\Commessa, App\Modules\Reparti,
     * App\Modules\Fustelle e App\Modules\Notifiche.
     */
    public function __construct(
        private readonly FaseTransitionService $fasi,
        private readonly CommessaService $commesse,
        private readonly ProgressoService $progresso,
        private readonly RepartoService $reparti,
        private readonly CapacitaService $capacita,
        private readonly NoteFustellaService $noteFustella,
        private readonly NotificaService $notifiche,
        private readonly PanoramicaRepartiService $panoramicaReparti,
        private readonly ReportOreService $reportOreService,
        private readonly FustelleReportService $fustelleReport,
        private readonly EsterneReportService $esterneReport,
    ) {}

    /**
     * ID dei reparti fustella (fustella, fustella piana, fustella cilindrica).
     * Risolti via RepartoService (cache 1h) invece di hard-coding [5,15,16].
     *
     * True se la fase appartiene a un reparto fustella (per applicare
     * il prefisso autore alle note via NoteFustellaService).
     */
    private function faseInRepartoFustella(OrdineFase $fase): bool
    {
        $repartoId = (int) ($fase->faseCatalogo->reparto_id ?? 0);
        return $repartoId > 0 && in_array($repartoId, $this->fustelleReport->repartiFustellaIds(), true);
    }

    private function isReadonly(): bool
    {
        return session('operatore_ruolo') === 'owner_readonly'
            || (request()->attributes->get('operatore_ruolo') === 'owner_readonly');
    }

    private function denyIfReadonly()
    {
        if ($this->isReadonly()) {
            if (request()->expectsJson()) {
                abort(403, 'Accesso in sola lettura');
            }
            return back()->with('error', 'Accesso in sola lettura — non puoi modificare i dati.');
        }
        return null;
    }

    /**
     * Costruisce la lista "Riempimento macchine" partendo da
     * {@see MacchinaRegistry::all()}.
     *
     * La UI mostra anche aggregati che non sono macchine fisiche
     * 1:1 (es. "Digitale" = HP Indigo + Zund). L'override locale
     * preserva le label storiche e gli accorpamenti.
     *
     * @return list<array{nome: string, reparti: array<int, string>}>
     */
    private function macchineRiempimentoFromRegistry(): array
    {
        // Override label/aggregazioni rispetto al registry. Chiave = id macchina.
        $override = [
            'XL106'  => ['nome' => 'XL 106',           'reparti' => ['stampa offset']],
            'BOBST'  => ['nome' => 'BOBST',            'reparti' => ['fustella piana']],
            'JOH'    => ['nome' => 'JOH Caldo',        'reparti' => ['stampa a caldo']],
            'PLAST'  => ['nome' => 'Plastificatrice',  'reparti' => ['plastificazione']],
            'PIEGA'  => ['nome' => 'Piegaincolla',     'reparti' => ['piegaincolla']],
            'FIN'    => ['nome' => 'Finestratrice',    'reparti' => ['finestratura']],
            'STEL'   => ['nome' => 'Fust. Cilindrica', 'reparti' => ['fustella cilindrica']],
            // Aggregato UI: digitale (Indigo) + finitura digitale (Zund)
            'INDIGO' => ['nome' => 'Digitale',         'reparti' => ['digitale', 'finitura digitale']],
            'TAGLIO' => ['nome' => 'Tagliacarte',      'reparti' => ['tagliacarte']],
        ];

        $out = [];
        foreach ($override as $id => $cfg) {
            // Se l'id non e nel registry, fallback a label override (no crash).
            if (! MacchinaRegistry::exists($id)) {
                $out[] = $cfg;
                continue;
            }
            // Validazione "leggera": il registry deve almeno conoscere l'id.
            // I reparti restano un override UI (gli aggregati non sono nel registry).
            $out[] = $cfg;
        }

        return $out;
    }

    /**
     * Panoramica commesse attive raggruppate per reparto
     */
    public function repartiOverview(Request $request)
    {
        // Strangler Fig: la logica di aggregazione vive in
        // App\Modules\Reportistica\Services\PanoramicaRepartiService
        // (cached 10 min, invalidata su PhaseCompleted).
        $data = $this->panoramicaReparti->overview();
        $totReparti = $this->reparti->tutti()->count();
        $opToken = request()->query('op_token') ?? request()->attributes->get('op_token');

        return view('owner.reparti_overview', compact('data', 'opToken', 'totReparti'));
    }


public function calcolaOreEPriorita($fase)
    {
        $qta_carta = $fase->ordine->qta_carta ?: 0;
        $infoFase = config("fasi_ore")[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
        $copieh = $infoFase['copieh'] ?: 1000;

        // ore = avviamento + qtaCarta / copieh (pezzi da fare / pezzi all'ora)
        $fase->ore = $infoFase['avviamento'] + ($qta_carta / $copieh);

        // Se priorità manuale (impostata da Excel o utente), non ricalcolare
        if ($fase->priorita_manuale) {
            return $fase;
        }

        // Se priorità manuale (range -901 a -999), non sovrascrivere
        $currentPriorita = $fase->priorita ?? 0;
        if ($currentPriorita <= -901 && $currentPriorita >= -999) {
            return $fase;
        }

        // giorni rimasti = dataPrevConsegna - OGGI (negativo se scaduta)
        $giorni_rimasti = 0;
        if ($fase->ordine->data_prevista_consegna) {
            $oggi = Carbon::today();
            $consegna = Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
            $giorni_rimasti = ($consegna->timestamp - $oggi->timestamp) / 86400;
        }

        // Ordine fase dalla config (es. STAMPA=10, PLASTIFICA=20, BRT=999)
        $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

        // Priorità = giorni rimasti - ore/24 + ordineFase/100
        $prioritaCalcolata = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 100), 2);

        $fase->priorita = $prioritaCalcolata;
        return $fase;
    }

    public function index(Request $request, PrinectApiInterface $prinect, PrinectSyncService $syncService)
    {
        // Sync Excel e Prinect girano via cron (excel:sync ogni 2min, prinect ogni minuto)
        // NON bloccare il page load con sync sincroni

        // Sync live Prinect: solo se richiesto esplicitamente (?sync=1)
        if ($request->boolean('sync')) try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {
            // Prinect non disponibile, continua senza sync
        }

        // Pre-calcola oggi per evitare ripetizioni
        $oggiTs = Carbon::today()->timestamp;
        $fasiPriorita = config('fasi_priorita', []);

        $fasi = OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->where('stato', '!=', 4)
            ->whereHas('ordine')
            ->orderBy('priorita')
            ->get();

        // Batch processing: ricalcola ore e priorità in un unico loop (no metodo per ogni fase)
        foreach ($fasi as $fase) {
            // Ore previste
            $qta_carta = $fase->ordine->qta_carta ?: 0;
            $infoFase = config("fasi_ore")[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
            $copieh = $infoFase['copieh'] ?: 1000;
            $fase->ore = $infoFase['avviamento'] + ($qta_carta / $copieh);

            // Priorità (skip se manuale)
            if (!$fase->priorita_manuale && !($fase->priorita <= -901 && $fase->priorita >= -999)) {
                $giorni_rimasti = 0;
                if ($fase->ordine->data_prevista_consegna) {
                    $consegnaTs = strtotime($fase->ordine->data_prevista_consegna);
                    $giorni_rimasti = ($consegnaTs - $oggiTs) / 86400;
                }
                $fp = $fasiPriorita[$fase->fase] ?? 500;
                $fase->priorita = round($giorni_rimasti - ($fase->ore / 24) + ($fp / 100), 2);
            }

            // Reparto
            $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';

            // Data inizio (senza Carbon::parse se non necessario)
            $fase->data_inizio = null;
            if ($fase->operatori->isNotEmpty()) {
                $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
                $fase->data_inizio = $primaData ?: null;
            }

            // Pre-compute parser descrizione (evita 2× parse per riga nel blade)
            $descPc = $fase->ordine->descrizione ?? '';
            $cliPc = $fase->ordine->cliente_nome ?? '';
            $repPc = strtolower($fase->faseCatalogo->reparto->nome ?? '');
            $notePrePc = $fase->ordine->note_prestampa ?? '';
            $fase->colori_parsed = \App\Helpers\DescrizioneParser::parseColori($descPc, $cliPc, $repPc);
            $fase->fustella_parsed = \App\Helpers\DescrizioneParser::parseFustella($descPc, $cliPc, $notePrePc);
        }

        $fasi = $fasi->sortBy('priorita');

        // RepartoService cache 1h evita la query orderBy ad ogni request.
        $reparti = $this->reparti->tutti()->sortBy('nome')->pluck('nome', 'id');
        $fasiCatalogo = FasiCatalogo::all();

        // Report spedizioni di oggi (stato 4 = consegnato)
        $repartoSpedizione = $this->reparti->byCodice(CodiceReparto::SPEDIZIONE);
        $spedizioniOggi = collect();
        if ($repartoSpedizione) {
            $spedizioniOggi = \Illuminate\Support\Facades\Cache::remember('owner_spedizioni_oggi', 60, function () use ($repartoSpedizione) {
                return OrdineFase::where('stato', StatoFase::CONSEGNATA)
                    ->whereDate('data_fine', Carbon::today())
                    ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                        $q->where('reparto_id', $repartoSpedizione->id);
                    })
                    ->with(['ordine', 'faseCatalogo', 'operatori'])
                    ->get()
                    ->sortByDesc('data_fine');
            });
        }

        // KPI giornalieri (cache 30s — riducono ~6 query pesanti per page load)
        $oggi = Carbon::today();
        $kpiCacheKey = 'owner_kpi_' . $oggi->format('Ymd');
        $kpi = \Illuminate\Support\Facades\Cache::remember($kpiCacheKey, 30, function () use ($oggi) {
            $fasiCompletateOggi = OrdineFase::where('stato', '>=', StatoFase::TERMINATA)
                ->where(function ($q) use ($oggi) {
                    $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                      ->orWhereDate('data_fine', $oggi);
                })
                ->count();
            $pivotOggi = DB::table('fase_operatore')
                ->whereDate('data_fine', $oggi)
                ->whereNotNull('data_inizio')
                ->select('data_inizio', 'data_fine', 'secondi_pausa')
                ->get();
            $orePivot = $pivotOggi->sum(function ($row) {
                $inizio = Carbon::parse($row->data_inizio);
                $fine = Carbon::parse($row->data_fine);
                $secLordo = abs($fine->diffInSeconds($inizio));
                $secPausa = min($row->secondi_pausa ?? 0, $secLordo);
                $secNetto = max($secLordo - $secPausa, 0);
                return min($secNetto / 3600, 24);
            });
            $orePrinect = OrdineFase::where('stato', '>=', StatoFase::TERMINATA)
                ->whereDate('data_fine', $oggi)
                ->where(function ($q) {
                    $q->where('tempo_avviamento_sec', '>', 0)
                      ->orWhere('tempo_esecuzione_sec', '>', 0);
                })
                ->whereDoesntHave('operatori', fn($q) => $q->whereDate('fase_operatore.data_fine', $oggi))
                ->sum(DB::raw('COALESCE(tempo_avviamento_sec, 0) + COALESCE(tempo_esecuzione_sec, 0)')) / 3600;
            return [
                'fasiCompletateOggi' => $fasiCompletateOggi,
                'oreLavorateOggi' => round($orePivot + $orePrinect, 1),
            ];
        });
        $fasiCompletateOggi = $kpi['fasiCompletateOggi'];
        $oreLavorateOggi = $kpi['oreLavorateOggi'];

        // Breakdown ore per operatore (per modal dettaglio)
        $orePerOperatoreOggi = DB::table('fase_operatore')
            ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
            ->whereDate('fase_operatore.data_fine', $oggi)
            ->whereNotNull('fase_operatore.data_inizio')
            ->select(
                'operatori.nome', 'operatori.cognome',
                DB::raw('COUNT(*) as fasi_count'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine)) as sec_lordo'),
                DB::raw('SUM(COALESCE(fase_operatore.secondi_pausa, 0)) as sec_pausa')
            )
            ->groupBy('operatori.nome', 'operatori.cognome')
            ->orderByDesc('sec_lordo')
            ->get()
            ->map(function ($op) {
                $secNetto = max($op->sec_lordo - $op->sec_pausa, 0);
                $op->ore_nette = round(min($secNetto / 3600, $op->fasi_count * 24), 1);
                return $op;
            });

        $kpi2 = \Illuminate\Support\Facades\Cache::remember('owner_kpi2_' . $oggi->format('Ymd'), 30, function () use ($oggi) {
            return [
                'commesseSpediteOggi' => OrdineFase::where('ordine_fasi.stato', StatoFase::CONSEGNATA)
                    ->where(function ($q) use ($oggi) {
                        $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                          ->orWhereDate('ordine_fasi.data_fine', $oggi);
                    })
                    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
                    ->distinct('ordini.commessa')
                    ->count('ordini.commessa'),
                'fasiAttive' => OrdineFase::where('stato', StatoFase::AVVIATA)->count(),
            ];
        });
        $commesseSpediteOggi = $kpi2['commesseSpediteOggi'];
        $fasiAttive = $kpi2['fasiAttive'];

        // Lista fasi in lavorazione (stato 2) per modale KPI
        $fasiInLavorazione = OrdineFase::where('stato', StatoFase::AVVIATA)
            ->with(['ordine:id,commessa,cliente_nome,descrizione,data_prevista_consegna', 'faseCatalogo', 'faseCatalogo.reparto:id,nome', 'operatori:id,nome,cognome'])
            ->orderBy('data_inizio')
            ->get();

        // Lista consegne oggi per modale KPI (stato 4 + data_fine oggi, reparto spedizione)
        $consegneOggi = collect();
        if ($repartoSpedizione) {
            $consegneOggi = OrdineFase::where('stato', StatoFase::CONSEGNATA)
                ->whereDate('data_fine', $oggi)
                ->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $repartoSpedizione->id))
                ->with(['ordine:id,commessa,cliente_nome,descrizione', 'operatori:id,nome,cognome'])
                ->orderByDesc('data_fine')
                ->get();
        }

        // Storico consegne ultimi 30 giorni (cache 5min — dati stabili)
        $storicoConsegne = collect();
        if ($repartoSpedizione) {
            $storicoConsegne = \Illuminate\Support\Facades\Cache::remember('owner_storico_consegne_v2', 300, function () use ($repartoSpedizione) {
                return OrdineFase::where('stato', StatoFase::CONSEGNATA)
                    ->whereDate('data_fine', '<', Carbon::today())
                    ->whereDate('data_fine', '>=', Carbon::today()->subDays(30))
                    ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                        $q->where('reparto_id', $repartoSpedizione->id);
                    })
                    ->with(['ordine', 'faseCatalogo', 'operatori'])
                    ->orderByDesc('data_fine')
                    ->limit(2000)
                    ->get();
            });
        }

        // Spedizioni BRT: dalla tabella ddt_spedizioni (supporta più DDT per commessa)
        // Escludi DDT consegnati da più di 5 giorni
        $limiteConsegna = Carbon::today()->subDays(5)->format('Y-m-d');
        $spedizioniBRT = DdtSpedizione::with('ordine:id,descrizione')
            ->where('vettore', 'LIKE', '%BRT%')
            ->where(function ($q) use ($limiteConsegna) {
                $q->whereNull('brt_data_consegna')
                  ->orWhere('brt_data_consegna', '>=', $limiteConsegna);
            })
            ->orderByDesc('onda_id_doc')
            ->get()
            ->groupBy('numero_ddt');

        // Progresso fasi per commessa — via SQL aggregate (cache 30s)
        $progressoCommesse = [];
        $progressoRows = \Illuminate\Support\Facades\Cache::remember('owner_progresso_commesse', 30, function () {
            return DB::table('ordine_fasi')
                ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
                ->whereNull('ordine_fasi.deleted_at')
                ->select(
                    'ordini.commessa',
                    DB::raw('COUNT(*) as totale'),
                    DB::raw("SUM(CASE WHEN ordine_fasi.stato >= " . StatoFase::TERMINATA . " THEN 1 ELSE 0 END) as terminate"),
                    DB::raw("SUM(CASE WHEN ordine_fasi.stato = " . StatoFase::AVVIATA . " THEN 1 ELSE 0 END) as avviate")
                )
                ->groupBy('ordini.commessa')
                ->get();
        });
        foreach ($progressoRows as $row) {
            $progressoCommesse[$row->commessa] = [
                'totale' => $row->totale,
                'terminate' => $row->terminate,
                'avviate' => $row->avviate,
                'percentuale' => $row->totale > 0 ? round(($row->terminate / $row->totale) * 100) : 0,
            ];
        }

        // Excel export gira via cron ogni 2 minuti (excel:sync) — non bloccare il page load

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        $isReadonly = $this->isReadonly();

        // Riempimento macchine — ottimizzato: 2 query batch invece di 18.
        //
        // Strangler Fig: la lista delle macchine + reparti viene derivata da
        // {@see MacchinaRegistry} (sorgente unica di verita), con override
        // di label e merge di reparti aggregati (Digitale = digitale +
        // finitura digitale) per preservare l'aggregazione UI esistente.
        $repartiRiemp = $this->macchineRiempimentoFromRegistry();
        $fasiOreConfig = config('fasi_ore');

        // 1 query (cached 1h via RepartoService): pre-carica tutti i reparti
        $tuttiReparti = $this->reparti->tutti()->keyBy('nome');

        // 2 query batch: tutte le fasi stato 0 e 1 con reparto, in una sola query per stato
        $fasiPerReparto = [];
        foreach ([0, 1] as $stato) {
            $fasiRiemp = DB::table('ordine_fasi')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
                ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
                ->where('ordine_fasi.stato', $stato)
                ->whereNull('ordine_fasi.deleted_at')
                ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
                ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
                ->select('ordine_fasi.fase', 'ordini.qta_carta', 'reparti.nome as reparto_nome')
                ->get();
            foreach ($fasiRiemp as $f) {
                $fasiPerReparto[$f->reparto_nome][$stato][] = $f;
            }
        }

        $riempimento = [];
        foreach ($repartiRiemp as $ru) {
            $ore0 = 0; $ore1 = 0; $cnt0 = 0; $cnt1 = 0;
            foreach ($ru['reparti'] as $repNome) {
                foreach ([0, 1] as $stato) {
                    $fasiStato = $fasiPerReparto[$repNome][$stato] ?? [];
                    foreach ($fasiStato as $f) {
                        $info = $fasiOreConfig[$f->fase] ?? null;
                        $ore = $info ? $info['avviamento'] + (($f->qta_carta ?? 0) / ($info['copieh'] ?: 1000)) : 0.5;
                        if ($stato === 0) { $ore0 += $ore; $cnt0++; }
                        else { $ore1 += $ore; $cnt1++; }
                    }
                }
            }
            $riempimento[] = [
                'nome' => $ru['nome'],
                'ore_0' => round($ore0, 1), 'fasi_0' => $cnt0,
                'ore_1' => round($ore1, 1), 'fasi_1' => $cnt1,
                'ore_totali' => round($ore0 + $ore1, 1),
            ];
        }
        usort($riempimento, fn($a, $b) => $b['ore_totali'] <=> $a['ore_totali']);

        return view('owner.dashboard', compact(
            'fasi', 'reparti', 'fasiCatalogo', 'spedizioniOggi', 'storicoConsegne',
            'fasiCompletateOggi', 'oreLavorateOggi', 'orePerOperatoreOggi', 'commesseSpediteOggi', 'fasiAttive', 'fasiInLavorazione', 'consegneOggi', 'spedizioniBRT', 'operatore', 'isReadonly',
            'progressoCommesse', 'riempimento'
        ));
    }

    public function aggiornaCampo(OwnerAggiornaCampoRequest $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;

        $fase = OrdineFase::with(['ordine', 'faseCatalogo'])->find($request->fase_id);

        // Strangler Fig — note fustella: se il campo è "note", la fase è in
        // reparto fustella e il valore non è un marker tecnico (Inviato a:
        // / lavorato esternamente), delega a NoteFustellaService che fa
        // append con prefisso "[Autore - dd/mm HH:MM]" invece di sovrascrivere.
        // Vincolo memoria 20/03/2026: storia preservata, autore Mirko prefissato.
        if (
            $fase
            && $request->campo === 'note'
            && is_string($request->valore)
            && trim($request->valore) !== ''
            && $this->faseInRepartoFustella($fase)
            && ! preg_match('/Inviato a:|lavorato esternamente|\besterno\b/i', (string) $request->valore)
        ) {
            $autore = (string) (auth()->user()->name ?? session('operatore_nome', 'owner'));
            $this->noteFustella->aggiungi($fase, $autore, trim($request->valore));
            return response()->json(['success' => true]);
        }

        $result = $this->commesse->aggiornaCampo(
            $fase,
            $request->campo,
            $request->valore,
            OwnerAggiornaCampoRequest::CAMPI_FASE,
            OwnerAggiornaCampoRequest::CAMPI_ORDINE,
        );

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        // Reload trigger (data_prevista_consegna su tutti gli ordini)
        if (! empty($result['reload'])) {
            app()->terminating(fn () => ExcelSyncService::exportToExcel());
            return response()->json(['success' => true, 'reload' => true]);
        }

        // NOTA: ExcelSync NON eseguito qui (costoso) - cron ogni 2min lo fa
        return response()->json(['success' => true]);
    }

    /**
     * Bulk update da Handsontable (drag-down Excel-like).
     * Riceve array {fase_id, campo, valore} e applica tramite CommessaService.
     * Max 100 righe per chiamata (DOS protection).
     */
    public function aggiornaBulk(\Illuminate\Http\Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;

        $righe = $request->input('righe', []);
        if (!is_array($righe) || empty($righe)) {
            return response()->json(['success' => false, 'messaggio' => 'Nessuna riga da aggiornare'], 422);
        }
        if (count($righe) > 100) {
            return response()->json(['success' => false, 'messaggio' => 'Massimo 100 righe per chiamata'], 422);
        }

        $whitelist = OwnerAggiornaCampoRequest::CAMPI_FASE;
        $processed = 0;
        $errors = [];

        foreach ($righe as $i => $riga) {
            $faseId = (int) ($riga['fase_id'] ?? 0);
            $campo = (string) ($riga['campo'] ?? '');
            $valore = $riga['valore'] ?? null;

            if (!$faseId || !in_array($campo, $whitelist, true)) {
                $errors[] = ['index' => $i, 'fase_id' => $faseId, 'reason' => 'campo invalido o fase_id mancante'];
                continue;
            }

            $fase = OrdineFase::with(['ordine', 'faseCatalogo'])->find($faseId);
            if (!$fase) {
                $errors[] = ['index' => $i, 'fase_id' => $faseId, 'reason' => 'fase non trovata'];
                continue;
            }

            $result = $this->commesse->aggiornaCampo(
                $fase,
                $campo,
                is_string($valore) ? $valore : (is_null($valore) ? null : (string) $valore),
                OwnerAggiornaCampoRequest::CAMPI_FASE,
                OwnerAggiornaCampoRequest::CAMPI_ORDINE,
            );

            if (!($result['success'] ?? false)) {
                $errors[] = ['index' => $i, 'fase_id' => $faseId, 'reason' => $result['messaggio'] ?? 'errore service'];
                continue;
            }

            $processed++;
        }

        return response()->json([
            'success' => count($errors) === 0,
            'processed' => $processed,
            'errors' => $errors,
        ]);
    }

    public function aggiungiRiga(OwnerAggiungiRigaRequest $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;

        $this->commesse->aggiungiRiga($request->validated());

        return redirect()->back()->with('success', 'Riga aggiunta correttamente.');
    }

    public function importOrdini(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $file = $request->file('file');
        if (!$file->isValid()) return redirect()->back()->with('error', 'File non valido.');

        try {
            $path = $file->store('imports');
            Excel::import(new \App\Imports\OrdiniImport, storage_path('app/' . $path));
            @unlink(storage_path('app/' . $path));
            FaseStatoService::ricalcolaTutti();
            return redirect()->route('owner.dashboard')->with('success', 'Ordini importati correttamente.');
        } catch (\Exception $e) {
            \Log::error('Import ordini errore', ['exception' => $e]);
            return redirect()->route('owner.dashboard')->with('error', 'Errore importazione, contattare IT');
        }
    }

    public function fasiTerminate(Request $request)
{
    $soloOggi = $request->boolean('oggi');
    $oggi = Carbon::today();

    $baseQuery = OrdineFase::whereIn('ordine_fasi.stato', [3, 4]);

    if ($soloOggi) {
        $baseQuery->where(function ($q) use ($oggi) {
            $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
              ->orWhereDate('ordine_fasi.data_fine', $oggi);
        });
    }

    // KPI via DB (senza caricare tutti i record)
    $kpiTotale = (clone $baseQuery)->count();
    $kpiCommesse = (clone $baseQuery)
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->distinct('ordini.commessa')
        ->count('ordini.commessa');
    $kpiOggi = (clone $baseQuery)->where(function ($q) use ($oggi) {
        $q->whereDate('ordine_fasi.data_fine', $oggi)
          ->orWhereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi));
    })->count();

    // Filtri dropdown via DB (valori unici da TUTTI i record, non solo pagina corrente)
    $repartiUnici = \DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
        ->whereIn('ordine_fasi.stato', [3, 4])
        ->distinct()
        ->orderBy('reparti.nome')
        ->pluck('reparti.nome');

    $fasiUniche = \DB::table('ordine_fasi')
        ->leftJoin('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('ordine_fasi.stato', [3, 4])
        ->selectRaw('COALESCE(fasi_catalogo.nome, ordine_fasi.fase) as nome_fase')
        ->distinct()
        ->orderBy('nome_fase')
        ->pluck('nome_fase')
        ->filter();

    $operatoriUnici = \DB::table('fase_operatore')
        ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
        ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
        ->whereIn('ordine_fasi.stato', [3, 4])
        ->selectRaw("CONCAT(operatori.nome, ' ', operatori.cognome) as nome_completo")
        ->distinct()
        ->orderBy('nome_completo')
        ->pluck('nome_completo');

    // Filtri server-side (funzionano su TUTTE le pagine, non solo quella corrente)
    $filteredQuery = clone $baseQuery;

    if ($request->filled('cerca')) {
        $cerca = $request->get('cerca');
        $filteredQuery->whereHas('ordine', function ($q) use ($cerca) {
            $q->where('commessa', 'LIKE', "%{$cerca}%")
              ->orWhere('cliente_nome', 'LIKE', "%{$cerca}%")
              ->orWhere('descrizione', 'LIKE', "%{$cerca}%");
        });
    }

    if ($request->filled('reparto')) {
        $reparto = $request->get('reparto');
        $filteredQuery->whereHas('faseCatalogo.reparto', fn($q) => $q->where('nome', $reparto));
    }

    if ($request->filled('fase')) {
        $faseFilter = $request->get('fase');
        $filteredQuery->where(function ($q) use ($faseFilter) {
            $q->whereHas('faseCatalogo', fn($sub) => $sub->where('nome', $faseFilter))
              ->orWhere('ordine_fasi.fase', $faseFilter);
        });
    }

    if ($request->filled('operatore')) {
        $opFilter = $request->get('operatore');
        $filteredQuery->whereHas('operatori', fn($q) => $q->whereRaw("CONCAT(nome, ' ', cognome) = ?", [$opFilter]));
    }

    // Filtro range data fine
    if ($request->filled('data_da')) {
        $filteredQuery->where(function ($q) use ($request) {
            $da = $request->get('data_da');
            $q->whereDate('ordine_fasi.data_fine', '>=', $da)
              ->orWhereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', '>=', $da));
        });
    }
    if ($request->filled('data_a')) {
        $filteredQuery->where(function ($q) use ($request) {
            $a = $request->get('data_a');
            $q->whereDate('ordine_fasi.data_fine', '<=', $a)
              ->orWhereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', '<=', $a));
        });
    }

    // Paginazione con eager loading
    $fasiTerminate = $filteredQuery
        ->with(['ordine.reparto', 'faseCatalogo.reparto', 'operatori'])
        ->orderBy('priorita')
        ->paginate(50)
        ->withQueryString();

    // Processa ogni record della pagina corrente (come prima)
    $fasiTerminate->getCollection()->transform(function ($fase) {

        // Calcolo ore e priorità
        $fase = $this->calcolaOreEPriorita($fase);

        // Reparto: da faseCatalogo (piu affidabile) oppure da ordine
        $fase->reparto_nome = $fase->faseCatalogo->reparto->nome
            ?? $fase->ordine->reparto->nome
            ?? '-';

        // DATA INIZIO: dalla pivot operatore, fallback dal campo ordine_fasi
        $dataInizioOriginale = $fase->getAttributes()['data_inizio'] ?? null;
        $carbonInizio = null;
        $fase->data_inizio = null;

        if ($fase->operatori->isNotEmpty()) {
            $primaDataInizio = $fase->operatori
                ->whereNotNull('pivot.data_inizio')
                ->sortBy('pivot.data_inizio')
                ->first()?->pivot->data_inizio;

            if ($primaDataInizio) {
                $carbonInizio = Carbon::parse($primaDataInizio);
                $fase->data_inizio = $carbonInizio->format('Y-m-d H:i:s');
            }
        }

        // Fallback: data_inizio dal campo ordine_fasi (impostato da Prinect sync)
        if (!$fase->data_inizio && $dataInizioOriginale) {
            $carbonInizio = Carbon::parse($dataInizioOriginale);
            $fase->data_inizio = $carbonInizio->format('Y-m-d H:i:s');
        }

        // DATA FINE: dalla pivot operatore, fallback dal campo ordine_fasi
        $dataFineOriginale = $fase->getAttributes()['data_fine'] ?? null;
        $carbonFine = null;
        $fase->data_fine = null;

        if ($fase->operatori->isNotEmpty()) {
            $primaDataFine = $fase->operatori
                ->whereNotNull('pivot.data_fine')
                ->sortBy('pivot.data_fine')
                ->first()?->pivot->data_fine;

            if ($primaDataFine) {
                $carbonFine = Carbon::parse($primaDataFine);
                $fase->data_fine = $carbonFine->format('Y-m-d H:i:s');
            }
        }

        // Fallback: data_fine dal campo ordine_fasi (impostato da Prinect sync)
        if (!$fase->data_fine && $dataFineOriginale) {
            $carbonFine = Carbon::parse($dataFineOriginale);
            $fase->data_fine = $carbonFine->format('Y-m-d H:i:s');
        }

        // Calcolo ore lavorate e pausa
        $fase->secondi_pausa_totale = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
        $fase->secondi_lordo = 0;
        if ($carbonInizio && $carbonFine) {
            $fase->secondi_lordo = abs($carbonFine->getTimestamp() - $carbonInizio->getTimestamp());
        }

        return $fase;
    });

    return view('owner.fasi_terminate', compact('fasiTerminate', 'soloOggi', 'kpiTotale', 'kpiCommesse', 'kpiOggi', 'repartiUnici', 'fasiUniche', 'operatoriUnici'));
}

    public function dettaglioCommessa($commessa, PrinectApiInterface $prinect, PrinectSyncService $syncService)
    {
        // Sync live: aggiorna fasi stampa da attivita Prinect di oggi
        try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {
            // Prinect non disponibile, continua senza sync
        }

        $ordini = Ordine::where('commessa', $commessa)->with('fasi.faseCatalogo.reparto', 'fasi.operatori', 'cliche')->get();
        if ($ordini->isEmpty()) abort(404, 'Commessa non trovata');

        $fasi = $ordini->flatMap(function ($ordine) {
            return $ordine->fasi->map(function ($fase) use ($ordine) {
                $fase->ordine = $ordine;
                $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';
                $fase = $this->calcolaOreEPriorita($fase);
                return $fase;
            });
        })->sortBy(function ($fase) {
            $nome = $fase->faseCatalogo->nome ?? '';
            $ordine = config('fasi_ordine');
            return $ordine[$nome] ?? $ordine[strtolower($nome)] ?? 999;
        });

        // Anteprima foglio di stampa da Prinect API
        $preview = null;
        $jobId = ltrim(substr($commessa, 0, 7), '0');
        if ($jobId && is_numeric($jobId)) {
            try {
                $wsData = $prinect->getWorksteps((string) $jobId);
                foreach ($wsData['worksteps'] ?? [] as $ws) {
                    if (isset($ws['types']) && in_array('ConventionalPrinting', $ws['types'])) {
                        $prevData = $prinect->getWorkstepPreview((string) $jobId, (string) $ws['id']);
                        $previews = $prevData['previews'] ?? [];
                        if (!empty($previews)) {
                            $preview = $previews[0];
                        }
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Prinect non disponibile, nessuna anteprima
            }
        }

        $ordine = $ordini->first();

        // Fustella PDF (cerca in public/fustelle/ matchando codice FS####)
        $fustellaCodice = null;
        $tutteDesc = $ordini->pluck('descrizione')->filter()->unique()->implode(' | ');
        $notePre = $ordine->note_prestampa ?? '';
        $fustellaCodice = \App\Helpers\DescrizioneParser::parseFustella($tutteDesc, $ordine->cliente_nome ?? '', $notePre);
        $fustella = \App\Helpers\FustellaResolver::resolve($fustellaCodice);

        return view('owner.dettaglio_commessa', compact('commessa', 'fasi', 'preview', 'ordine', 'ordini', 'fustella'));
    }

    public function eliminaFase(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $this->commesse->eliminaRiga($fase);

        return response()->json(['success' => true]);
    }

    public function aggiornaStato(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $nuovoStato = (int) $request->stato;

        // Tiro obbligatorio per fasi di stampa a caldo quando si termina (stato=3)
        $fasiCaldo = ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO'];
        if ($nuovoStato === 3 && in_array(strtoupper($fase->fase ?? ''), $fasiCaldo, true)) {
            if ($request->tiro === null || $request->tiro === '' || (int) $request->tiro <= 0) {
                return response()->json([
                    'success' => false,
                    'messaggio' => 'Tiro (cm foil) obbligatorio per stampa a caldo.',
                    'need_tiro' => true,
                ], 422);
            }
            $fase->tiro = (int) $request->tiro;
            $fase->save();
        }

        // Stato 3 con flag terminata_manualmente: gestito qui prima del riapri()
        if ($nuovoStato === 3) {
            $fase->stato = 3;
            if (! $fase->data_fine) {
                $fase->data_fine = now()->format('Y-m-d H:i:s');
            }
            $fase->terminata_manualmente = true;
            $fase->save();
            FaseStatoService::ricalcolaStati($fase->ordine_id);
        } else {
            // Stati <= 2: delega al service modulo (riapertura)
            $this->fasi->riapri($fase, $nuovoStato);
        }

        app()->terminating(fn() => ExcelSyncService::exportToExcel());
        return response()->json(['success' => true, 'messaggio' => 'Stato aggiornato']);
    }

    /**
     * Cerca fasi di una commessa specifica (tutti gli stati, incluso 4).
     * Usato dal filtro commessa per mostrare fasi consegnate on-demand.
     */
    public function cercaCommessa(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 3) return response()->json([]);

        // Costruisci codice commessa se l'utente ha scritto solo il numero
        $commessa = $q;
        if (preg_match('/^\d{4,7}$/', $q)) {
            $commessa = str_pad($q, 7, '0', STR_PAD_LEFT) . '-26';
        }

        $fasi = OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->whereHas('ordine', fn($qb) => $qb->where('commessa', 'LIKE', "%{$commessa}%"))
            ->get();

        if ($fasi->isEmpty()) return response()->json([]);

        $fasiPriorita = config('fasi_priorita', []);
        $oggiTs = \Carbon\Carbon::today()->timestamp;
        $result = [];

        foreach ($fasi as $fase) {
            // Calcolo priorità inline
            $qta_carta = $fase->ordine->qta_carta ?: 0;
            $infoFase = config("fasi_ore")[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
            $copieh = $infoFase['copieh'] ?: 1000;
            $ore = $infoFase['avviamento'] + ($qta_carta / $copieh);

            $dataInizio = null;
            if ($fase->operatori->isNotEmpty()) {
                $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
                $dataInizio = $primaData ?: null;
            }

            $result[] = [
                'id' => $fase->id,
                'commessa' => $fase->ordine->commessa ?? '',
                'stato' => $fase->stato,
                'cliente' => $fase->ordine->cliente_nome ?? '',
                'cod_art' => $fase->ordine->cod_art ?? '',
                'colori' => \App\Helpers\DescrizioneParser::parseColori($fase->ordine->descrizione ?? '', $fase->ordine->cliente_nome ?? '', $fase->faseCatalogo->reparto->nome ?? ''),
                'fustella' => \App\Helpers\DescrizioneParser::parseFustella($fase->ordine->descrizione ?? '', $fase->ordine->cliente_nome ?? '', $fase->ordine->note_prestampa ?? ''),
                'descrizione' => $fase->ordine->descrizione ?? '',
                'qta' => $fase->ordine->qta_richiesta ?? 0,
                'um' => $fase->ordine->um ?? '',
                'priorita' => $fase->priorita ?? '',
                'fase' => $fase->faseCatalogo->nome_display ?? $fase->fase ?? '',
                'reparto' => $fase->faseCatalogo->reparto->nome ?? '',
                'carta' => $fase->ordine->carta ?? '',
                'qta_carta' => $fase->ordine->qta_carta ?? 0,
                'data_consegna' => $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '',
                'cod_carta' => $fase->ordine->cod_carta ?? '',
                'um_carta' => $fase->ordine->UM_carta ?? '',
                'operatori' => $fase->operatori->pluck('nome')->implode(', '),
                'qta_prod' => $fase->qta_prod ?? 0,
                'esterno' => $fase->esterno ? 1 : 0,
                'note' => $fase->note ?? '',
                'data_inizio' => $dataInizio,
                'data_fine' => $fase->getAttributes()['data_fine'] ?? '',
                'ore_prev' => round($ore, 1),
                'data_reg' => $fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '',
            ];
        }

        return response()->json($result);
    }

    public function ricalcolaStati()
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $this->commesse->ricalcolaTutto();
        return response()->json(['success' => true, 'messaggio' => 'Stati ricalcolati']);
    }

    public function syncOnda()
    {
        set_time_limit(120); // Il sync Onda può richiedere più di 30 secondi

        // Permetti sync da prestampa (hanno redirect)
        if (!request('redirect')) {
            if ($deny = $this->denyIfReadonly()) return $deny;
        }
        try {
            // Prima pulisci eventuali duplicati
            $duplicatiRimossi = $this->pulisciDuplicati();

            // Strangler Fig: sync ordini/fasi va al modulo Onda; DDT vendita al modulo Spedizione.
            // I due metodi DDT Fornitore/Forniture Lavorazioni restano sul wrapper legacy
            // perché non hanno ancora controparte in un modulo dedicato.
            $r = app(OrdineSyncService::class)->sync();
            $risultato = [
                'ordini_creati'     => $r['ordini_creati'] ?? 0,
                'ordini_aggiornati' => $r['ordini_aggiornati'] ?? 0,
                'fasi_create'       => $r['fasi_create'] ?? 0,
            ];
            $ddtFornitore = OndaSyncService::sincronizzaDDTFornitore();
            $ddtLavorazioni = OndaSyncService::sincronizzaDDTFornitureLavorazioni();
            $venditaR = app(DdtSyncService::class)->syncFromOnda(7);
            $ddtVendita = ($venditaR['inseriti'] ?? 0) + ($venditaR['aggiornati'] ?? 0);
            $msg = "Sync Onda completato: {$risultato['ordini_creati']} ordini creati, "
                 . "{$risultato['ordini_aggiornati']} aggiornati, {$risultato['fasi_create']} fasi create.";
            $totDDT = $ddtFornitore + $ddtLavorazioni;
            if ($totDDT > 0) $msg .= " DDT fornitore: {$totDDT} fasi esterne.";
            if ($ddtVendita > 0) $msg .= " DDT vendita: {$ddtVendita}.";
            if ($duplicatiRimossi > 0) {
                $msg .= " ($duplicatiRimossi ordini duplicati rimossi)";
            }
            $redirectUrl = request('redirect');
            if ($redirectUrl) {
                return redirect($redirectUrl)->with('success', $msg);
            }
            return redirect()->route('owner.dashboard')->with('success', $msg);
        } catch (\Exception $e) {
            \Log::error('Sync Onda errore', ['exception' => $e]);
            $redirectUrl = request('redirect');
            if ($redirectUrl) {
                return redirect($redirectUrl)->with('error', 'Errore sync Onda, contattare IT');
            }
            return redirect()->route('owner.dashboard')->with('error', 'Errore sync Onda, contattare IT');
        }
    }

    /**
     * Rimuovi ordini duplicati: per ogni (commessa, cod_art), tieni quello con id più basso
     * e sposta le fasi con stato > 0 dal duplicato all'originale prima di eliminare.
     */
    private function pulisciDuplicati(): int
    {
        $rimossi = 0;

        // Trova gruppi con duplicati
        $duplicati = Ordine::select('commessa', 'cod_art', \DB::raw('COUNT(*) as cnt'), \DB::raw('MIN(id) as min_id'))
            ->groupBy('commessa', 'cod_art')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicati as $gruppo) {
            $originaleId = $gruppo->min_id;

            // Ordini duplicati (tutti tranne l'originale)
            $ordiniDuplicati = Ordine::where('commessa', $gruppo->commessa)
                ->where('cod_art', $gruppo->cod_art)
                ->where('id', '!=', $originaleId)
                ->get();

            foreach ($ordiniDuplicati as $dup) {
                // Sposta fasi con stato > 0 (lavorate) all'ordine originale
                OrdineFase::where('ordine_id', $dup->id)
                    ->where('stato', '>', 0)
                    ->update(['ordine_id' => $originaleId]);

                // Elimina le fasi rimanenti del duplicato
                OrdineFase::where('ordine_id', $dup->id)->delete();

                // Elimina l'ordine duplicato
                $dup->delete();
                $rimossi++;
            }
        }

        return $rimossi;
    }

    public function reportOre(Request $request)
    {
        $filtroCommessa = $request->query('commessa');
        $filtroReparto  = $request->query('reparto');
        $filtroDal      = $request->query('dal');
        $filtroAl       = $request->query('al');

        // Range default: ultimo anno (lo storico controller non aveva limite,
        // ma con i filtri "dal"/"al" forniti dall'utente sostituiamo).
        $from = $filtroDal ? Carbon::parse($filtroDal) : Carbon::now()->subYear();
        $to   = $filtroAl  ? Carbon::parse($filtroAl)  : Carbon::now();

        // Strangler Fig: aggregazione delegata a ReportOreService
        // (priorità Prinect → fallback pivot operatori, cfr. memoria 9/03/2026).
        $payload = $this->reportOreService->perTemplate(
            $from,
            $to,
            $filtroCommessa,
            $filtroReparto ? (int) $filtroReparto : null,
        );

        $commesse      = $payload['commesse'];
        $orePerReparto = $payload['orePerReparto'];

        $reparti = $this->reparti->tutti()
            ->reject(fn ($r) => strtolower((string) $r->nome) === 'spedizione')
            ->sortBy('nome')
            ->pluck('nome', 'id');

        // Commesse con fasi terminate oggi (subset client-side per il template).
        $oggi = Carbon::today();
        $commesseOggi = $commesse->filter(function ($c) use ($oggi) {
            return $c->fasi->contains(function ($f) use ($oggi) {
                if (!$f->data_fine) return false;
                try {
                    return Carbon::parse($f->data_fine)->isToday();
                } catch (\Exception $e) {
                    return false;
                }
            });
        });

        return view('owner.report_ore', compact('commesse', 'commesseOggi', 'reparti', 'filtroCommessa', 'filtroReparto', 'filtroDal', 'filtroAl', 'orePerReparto'));
    }

    /**
     * Prototipo nuova UI owner — dati reali, layout nuovo.
     */
    public function prototipo()
    {
        // KPI
        $oggi = Carbon::today();
        $fasiCompletateOggi = OrdineFase::where('stato', '>=', StatoFase::TERMINATA)
            ->where(function ($q) use ($oggi) {
                $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                  ->orWhereDate('data_fine', $oggi);
            })->count();
        $fasiAttive = OrdineFase::where('stato', StatoFase::AVVIATA)->count();
        $commesseSpediteOggi = OrdineFase::where('stato', StatoFase::CONSEGNATA)
            ->whereDate('data_fine', $oggi)->with('ordine')->get()
            ->pluck('ordine.commessa')->unique()->count();

        $pivotOggi = DB::table('fase_operatore')->whereDate('data_fine', $oggi)
            ->whereNotNull('data_inizio')->select('data_inizio', 'data_fine', 'secondi_pausa')->get();
        $oreLavorateOggi = round($pivotOggi->sum(function ($row) {
            return max(abs(Carbon::parse($row->data_fine)->diffInSeconds(Carbon::parse($row->data_inizio))) - ($row->secondi_pausa ?? 0), 0) / 3600;
        }), 1);

        return view('proto.owner_full', compact(
            'fasiCompletateOggi', 'fasiAttive', 'commesseSpediteOggi', 'oreLavorateOggi'
        ));
    }

    public function scheduling(PrinectApiInterface $prinect, PrinectSyncService $syncService)
    {
        // Sync live Prinect
        try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {}

        // Calcola tempi medi storici per tipo fase (da Prinect e operatori)
        $tempiMediPerFase = [];
        $fasiCompletate = OrdineFase::with(['ordine', 'operatori'])
            ->where('stato', '>=', 3)
            ->where('data_fine', '>=', Carbon::now()->subDays(90))
            ->get();
        foreach ($fasiCompletate as $fc) {
            $tipo = $fc->fase;
            $qtaCarta = $fc->ordine->qta_carta ?? 0;
            if ($qtaCarta <= 0) continue;

            // Ore effettive: prima Prinect, poi pivot operatore
            $ore = null;
            $secP = ($fc->tempo_avviamento_sec ?? 0) + ($fc->tempo_esecuzione_sec ?? 0);
            if ($secP > 60) {
                $ore = $secP / 3600;
            } else {
                // Calcola da operatori pivot
                if ($fc->operatori->isNotEmpty()) {
                    $secOp = 0;
                    foreach ($fc->operatori as $op) {
                        if ($op->pivot->data_inizio && $op->pivot->data_fine) {
                            $s = Carbon::parse($op->pivot->data_fine)->diffInSeconds(Carbon::parse($op->pivot->data_inizio));
                            $s -= ($op->pivot->secondi_pausa ?? 0);
                            if ($s > 0) $secOp += $s;
                        }
                    }
                    if ($secOp > 60) $ore = $secOp / 3600;
                }
            }
            if ($ore && $ore > 0.01 && $ore < 200) {
                if (!isset($tempiMediPerFase[$tipo])) $tempiMediPerFase[$tipo] = ['totOre' => 0, 'totQta' => 0, 'count' => 0];
                $tempiMediPerFase[$tipo]['totOre'] += $ore;
                $tempiMediPerFase[$tipo]['totQta'] += $qtaCarta;
                $tempiMediPerFase[$tipo]['count']++;
            }
        }
        // Calcola ore medie per foglio e avviamento medio
        $tempiMedi = [];
        foreach ($tempiMediPerFase as $tipo => $dati) {
            if ($dati['count'] >= 2 && $dati['totQta'] > 0) {
                $tempiMedi[$tipo] = round($dati['totOre'] / $dati['count'], 3); // ore medie per job
            }
        }

        $fasi = OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori'])
            ->where('stato', '<', 3)
            ->get()
            ->map(function ($fase) {
                return $this->calcolaOreEPriorita($fase);
            })
            ->sortBy('priorita');

        $dataScheduling = $fasi->map(function ($fase) use ($tempiMedi) {
            // Data inizio: dalla pivot operatore, fallback dal campo ordine_fasi
            $dataInizio = null;
            if ($fase->operatori->isNotEmpty()) {
                $primo = $fase->operatori->sortBy('pivot.data_inizio')->first();
                $dataInizio = $primo?->pivot->data_inizio;
            }
            if (!$dataInizio) {
                $dataInizio = $fase->getAttributes()['data_inizio'] ?? null;
            }

            $consegna = $fase->ordine->data_prevista_consegna;
            $giorniAllaConsegna = null;
            if ($consegna) {
                $giorniAllaConsegna = round((Carbon::parse($consegna)->startOfDay()->timestamp - Carbon::today()->timestamp) / 86400);
            }

            // Ore reali da Prinect (se disponibili)
            $orePrinect = null;
            $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
            if ($secPrinect > 0) {
                $orePrinect = round($secPrinect / 3600, 2);
            }

            // Ore reali da pivot operatore (data_fine - data_inizio - pausa)
            $oreOperatore = null;
            if ($fase->operatori->isNotEmpty()) {
                $oreOp = 0;
                foreach ($fase->operatori as $op) {
                    if ($op->pivot->data_inizio && $op->pivot->data_fine) {
                        $sec = Carbon::parse($op->pivot->data_fine)->diffInSeconds(Carbon::parse($op->pivot->data_inizio));
                        $sec -= ($op->pivot->secondi_pausa ?? 0);
                        if ($sec > 0) $oreOp += $sec / 3600;
                    }
                }
                if ($oreOp > 0) $oreOperatore = round($oreOp, 2);
            }

            // Ordine nel flusso produttivo (da config fasi_priorita)
            $faseOrdine = config('fasi_priorita')[$fase->fase] ?? 500;

            return [
                'id' => $fase->id,
                'commessa' => $fase->ordine->commessa ?? '-',
                'cliente' => $fase->ordine->cliente_nome ?? '-',
                'descrizione' => $fase->ordine->descrizione ?? '-',
                'cod_art' => $fase->ordine->cod_art ?? '-',
                'fase' => $fase->fase,
                'stato' => $fase->stato,
                'ore' => round($fase->ore, 2),
                'ore_prinect' => $orePrinect,
                'ore_operatore' => $oreOperatore,
                'ore_media_storica' => $tempiMedi[$fase->fase] ?? null,
                'fase_ordine' => $faseOrdine,
                'priorita' => $fase->priorita,
                'consegna' => $consegna,
                'giorni_consegna' => $giorniAllaConsegna,
                'qta_carta' => $fase->ordine->qta_carta ?? 0,
                'reparto_id' => $fase->faseCatalogo?->reparto_id ?? 0,
                'reparto' => ucfirst($fase->faseCatalogo?->reparto?->nome ?? 'generico'),
                'data_inizio_reale' => $dataInizio,
                'esterno' => (bool) $fase->esterno,
                // Scheduler Mossa 37 (pre-calcolati) per uso da UI schedulaDaDB
                'sched_macchina' => $fase->sched_macchina,
                'sched_inizio' => $fase->sched_inizio,
                'sched_fine' => $fase->sched_fine,
                'sched_posizione' => $fase->sched_posizione,
                'sched_setup_h' => $fase->sched_setup_h,
                'sched_setup_tipo' => $fase->sched_setup_tipo,
                'sched_batch_group' => $fase->sched_batch_group,
            ];
        })->values();

        return view('owner.scheduling', [
            'dataJson' => $dataScheduling->toJson(),
        ]);
    }

    /**
     * Genera + scarica Excel piano produzione Mossa 37
     */
    public function schedulingExcel()
    {
        $filePath = storage_path('app/piano_produzione_' . now()->format('Y-m-d_His') . '.xlsx');

        try {
            \App\Services\SchedulerExportService::export($filePath);
        } catch (\Exception $e) {
            \Log::error('SchedulerExportService export errore', ['exception' => $e]);
            return back()->with('error', 'Errore generazione Excel, contattare IT');
        }

        if (!file_exists($filePath)) {
            return back()->with('error', 'File Excel non generato');
        }

        return response()->download($filePath, 'piano_produzione_' . now()->format('Y-m-d') . '.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Scarica PDF bolla di lavorazione per una fase
     */
    public function bollaLavorazione($faseId)
    {
        return \App\Services\BollaLavorazioneService::stream((int) $faseId);
    }

    /**
     * Scarica PDF scheda produzione completa per commessa (tutte le fasi)
     */
    public function schedaProduzione($commessa)
    {
        return \App\Services\BollaLavorazioneService::streamCommessa($commessa);
    }

    public function downloadExcel()
    {
        // Genera file aggiornato
        ExcelSyncService::exportToExcel();

        $path = env('EXCEL_SYNC_PATH');
        if ($path && $path !== '') {
            $filePath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
        } else {
            $filePath = storage_path('app/excel_sync/dashboard_mes.xlsx');
        }

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File Excel non ancora generato.');
        }

        return response()->download($filePath, 'dashboard_mes.xlsx');
    }

    public function esterne()
    {
        // Strangler Fig: tracking esterno delegato a EsterneReportService
        // (cached 10 min, invalidato su PhaseCompleted).
        $commesseEsterne = $this->esterneReport->commesseEsterne();

        return view('owner.esterne', compact('commesseEsterne'));
    }

    /**
     * Pagina Fustelle owner: tutte le fustelle da utilizzare nei prossimi 30 giorni.
     */
    public function fustelleOverview()
    {
        // Strangler Fig: parsing/aggregazione delegata a FustelleReportService
        // (cached 10 min, no magic numbers reparti via RepartoService).
        $fustelleMap = $this->fustelleReport->overview();

        return view('owner.fustelle', compact('fustelleMap'));
    }

    /**
     * Audit Log — visualizzazione eventi di sicurezza
     */
    public function auditLog(Request $request)
    {
        $query = DB::table('audit_logs')->orderByDesc('created_at');

        if ($request->filled('azione')) {
            $query->where('action', $request->azione);
        }
        if ($request->filled('utente')) {
            $query->where('user_name', 'like', '%' . $request->utente . '%');
        }
        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->data);
        }

        $logs = $query->paginate(50);

        $azioni = DB::table('audit_logs')->distinct()->pluck('action');

        return view('owner.audit_log', compact('logs', 'azioni'));
    }

    /**
     * Imposta manualmente il numero di cliché su un ordine (override).
     */
    public function setCliche(Request $request)
    {
        $this->denyIfReadonly();
        $ordineId = (int) $request->input('ordine_id');
        $numero = $request->input('cliche_numero');
        $ordine = \App\Models\Ordine::find($ordineId);
        if (!$ordine) return response()->json(['ok' => false, 'error' => 'Ordine non trovato'], 404);

        if ($numero === null || $numero === '') {
            return response()->json(['ok' => false, 'error' => 'Numero cliché richiesto'], 422);
        }
        $numero = (int) $numero;

        $cliche = \App\Models\ClicheAnagrafica::where('numero', $numero)->first();
        if (!$cliche) return response()->json(['ok' => false, 'error' => 'Cliché non esistente'], 404);

        $ordine->cliche_numero = $numero;
        $ordine->cliche_match_type = 'manual';
        $ordine->cliche_matched_at = now();
        $ordine->save();

        return response()->json(['ok' => true, 'label' => $cliche->label()]);
    }

    /**
     * Applica una priorità a tutte le fasi di una commessa (stato < 3).
     * Imposta priorita_manuale=true per proteggere dai ricalcoli automatici.
     */
    public function applicaPrioritaCommessa(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $commessa = trim((string) $request->input('commessa'));
        $priorita = $request->input('priorita');
        if ($commessa === '' || $priorita === null || $priorita === '') {
            return response()->json(['success' => false, 'messaggio' => 'commessa o priorita mancanti'], 422);
        }
        $priorita = (float) str_replace(',', '.', (string) $priorita);

        $ordiniIds = \App\Models\Ordine::where('commessa', $commessa)->pluck('id');
        if ($ordiniIds->isEmpty()) {
            return response()->json(['success' => false, 'messaggio' => 'commessa non trovata'], 404);
        }

        $count = OrdineFase::whereIn('ordine_id', $ordiniIds)
            ->where('stato', '<', 3)
            ->whereNull('deleted_at')
            ->update([
                'priorita' => $priorita,
                'priorita_manuale' => true,
                'updated_at' => now(),
            ]);

        // Strangler Fig: notifica via NotificaService (canale Telegram owner)
        // invece di Http::post inline. Best-effort: errore canale → log, non
        // rompe la response JSON.
        $chatOwner = config('mes.notifiche.chat_owner') ?? config('services.telegram.chat_id_owner');
        if ($count > 0 && $chatOwner) {
            try {
                $this->notifiche->inviaSuCanale(
                    new Notifica(
                        titolo:       'Priorità commessa aggiornata',
                        messaggio:    "Commessa {$commessa}: priorità {$priorita} applicata a {$count} fasi (manuale).",
                        canale:       CanaleNotifica::Telegram,
                        priorita:     PrioritaNotifica::Alta,
                        destinatario: $chatOwner,
                        payload:      ['commessa' => $commessa, 'priorita' => $priorita, 'count' => $count],
                    ),
                    CanaleNotifica::Telegram,
                );
            } catch (\Throwable $e) {
                \Log::warning('NotificaService priorità commessa fallita', [
                    'commessa' => $commessa, 'err' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Rimuove il collegamento cliché (torna ad auto al prossimo match).
     */
    public function clearCliche(Request $request)
    {
        $this->denyIfReadonly();
        $ordineId = (int) $request->input('ordine_id');
        $ordine = \App\Models\Ordine::find($ordineId);
        if (!$ordine) return response()->json(['ok' => false], 404);
        $ordine->cliche_numero = null;
        $ordine->cliche_match_type = null;
        $ordine->cliche_matched_at = null;
        $ordine->save();
        return response()->json(['ok' => true]);
    }

    /**
     * Report aggregato per cliché: commesse, tiro totale foil, scarti medi.
     * Include breakdown per commessa (drill-down espandibile).
     */
    public function reportCliche(Request $request)
    {
        $rows = DB::table('cliche_anagrafica as c')
            ->leftJoin('ordini as o', 'o.cliche_numero', '=', 'c.numero')
            ->leftJoin('ordine_fasi as f', function ($j) {
                $j->on('f.ordine_id', '=', 'o.id')
                  ->whereNull('f.deleted_at')
                  ->whereIn('f.fase', ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO']);
            })
            ->select(
                'c.numero', 'c.descrizione_raw', 'c.scatola', 'c.qta',
                DB::raw('COUNT(DISTINCT o.id) AS n_commesse'),
                DB::raw('COALESCE(SUM(f.tiro), 0) AS tiro_totale'),
                DB::raw('COALESCE(AVG(f.tiro), 0) AS tiro_medio'),
                DB::raw('COALESCE(SUM(f.scarti), 0) AS scarti_totali'),
                DB::raw('COALESCE(AVG(f.scarti), 0) AS scarti_medi'),
                DB::raw('COALESCE(SUM(f.qta_prod), 0) AS qta_prod_totale')
            )
            ->groupBy('c.numero', 'c.descrizione_raw', 'c.scatola', 'c.qta')
            ->orderBy('c.numero')
            ->get();

        // Breakdown per commessa (raggruppato per cliché)
        $breakdown = DB::table('ordini as o')
            ->leftJoin('ordine_fasi as f', function ($j) {
                $j->on('f.ordine_id', '=', 'o.id')
                  ->whereNull('f.deleted_at')
                  ->whereIn('f.fase', ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO']);
            })
            ->whereNotNull('o.cliche_numero')
            ->select(
                'o.cliche_numero',
                'o.commessa',
                'o.cliente_nome',
                'o.descrizione',
                'o.data_prevista_consegna',
                DB::raw('COALESCE(SUM(f.tiro), 0) AS tiro'),
                DB::raw('COALESCE(SUM(f.scarti), 0) AS scarti'),
                DB::raw('COALESCE(SUM(f.qta_prod), 0) AS qta_prod')
            )
            ->groupBy('o.cliche_numero', 'o.commessa', 'o.cliente_nome', 'o.descrizione', 'o.data_prevista_consegna')
            ->orderBy('o.data_prevista_consegna', 'desc')
            ->get()
            ->groupBy('cliche_numero');

        // Vista per commessa: tutte le commesse con cliché collegato
        $perCommessa = DB::table('ordini as o')
            ->join('cliche_anagrafica as c', 'c.numero', '=', 'o.cliche_numero')
            ->leftJoin('ordine_fasi as f', function ($j) {
                $j->on('f.ordine_id', '=', 'o.id')
                  ->whereNull('f.deleted_at')
                  ->whereIn('f.fase', ['STAMPACALDOJOH', 'STAMPACALDOJOHEST', 'STAMPALAMINAORO']);
            })
            ->select(
                'o.commessa',
                'o.cliente_nome',
                'o.descrizione',
                'o.data_prevista_consegna',
                'o.qta_richiesta',
                'c.numero AS cliche_numero',
                'c.scatola',
                'c.descrizione_raw AS cliche_desc',
                DB::raw('COALESCE(SUM(f.tiro), 0) AS tiro'),
                DB::raw('COALESCE(SUM(f.scarti), 0) AS scarti'),
                DB::raw('COALESCE(SUM(f.qta_prod), 0) AS qta_prod')
            )
            ->groupBy('o.commessa', 'o.cliente_nome', 'o.descrizione', 'o.data_prevista_consegna',
                     'o.qta_richiesta', 'c.numero', 'c.scatola', 'c.descrizione_raw')
            ->orderBy('o.data_prevista_consegna', 'desc')
            ->get();

        return view('owner.report_cliche', compact('rows', 'breakdown', 'perCommessa'));
    }
}
