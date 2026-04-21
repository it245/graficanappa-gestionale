<?php

namespace App\Http\Controllers;

use App\Http\Services\FieryService;
use App\Http\Services\FierySyncService;
use App\Models\ContatoreStampante;
use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Http\Request;

class FieryController extends Controller
{
    public function index(FieryService $fiery, FierySyncService $syncService)
    {
        set_time_limit(60);

        $status = $fiery->getServerStatus();

        // Se Fiery offline, non tentare altre chiamate API (risparmia 15s+ di timeout)
        if (!$status) {
            $jobData = ['printing' => null, 'queue' => [], 'completed' => [], 'total' => 0];
            $snmp = \Cache::get('fiery_snmp_live', []);
            return view('fiery.dashboard', compact('status', 'jobData', 'snmp'));
        }

        $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);

        // Job list da API v5
        $jobs = $fiery->getJobs();
        $accounting = $fiery->getAccountingPerCommessa();
        $jobData = $this->organizzaJobs($jobs, $accounting);

        // SNMP cachato 30s
        $snmp = \Cache::remember('fiery_snmp_live', 30, fn() => $this->leggiContatoriSnmp());

        return view('fiery.dashboard', compact('status', 'jobData', 'snmp'));
    }

    /**
     * Endpoint JSON: livelli consumabili (toner, waste, ADF) per widget real-time.
     */
    public function consumablesJson(FieryService $fiery, Request $request)
    {
        // Debug: ?raw=1 torna payload grezzo per scoprire struttura JSON reale
        if ($request->has('raw')) {
            return response()->json(['raw' => $fiery->getConsumablesRaw()]);
        }

        $consumables = $fiery->getConsumables();
        if ($consumables === null) {
            return response()->json(['success' => false, 'msg' => 'Fiery non raggiungibile']);
        }
        // Calcola alert auto (< 10% = warning, < 5% = critical)
        $alerts = [];
        foreach ($consumables as $c) {
            if ($c['type'] === 'toner' || str_contains(strtolower($c['name']), 'toner')) {
                if ($c['level'] < 5) $alerts[] = "TONER CRITICO: {$c['name']} al {$c['level']}%";
                elseif ($c['level'] < 10) $alerts[] = "Toner basso: {$c['name']} al {$c['level']}%";
            }
        }
        return response()->json([
            'success' => true,
            'consumables' => $consumables,
            'alerts' => $alerts,
            'updated_at' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Info estesa server Fiery (modello, seriale, firmware, capabilities).
     */
    public function infoJson(FieryService $fiery)
    {
        return response()->json([
            'success' => true,
            'info' => $fiery->getInfoExtended(),
            'version' => $fiery->getVersion(),
        ]);
    }

    public function statusJson(FieryService $fiery, FierySyncService $syncService)
    {
        set_time_limit(60);

        $status = $fiery->getServerStatus();

        if (!$status) {
            return response()->json([
                'online' => false,
                'stato' => 'offline',
                'avviso' => 'Fiery non raggiungibile',
            ]);
        }

        $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);

        // Job list da API v5
        $jobs = $fiery->getJobs();
        $accounting = $fiery->getAccountingPerCommessa();
        $status['jobs'] = $this->organizzaJobs($jobs, $accounting);
        $status['snmp'] = \Cache::remember('fiery_snmp_live', 30, fn() => $this->leggiContatoriSnmp());

        return response()->json($status);
    }

    /**
     * Organizza i job in categorie per la dashboard
     */
    private function organizzaJobs(?array $jobs, ?array $accounting = null): array
    {
        if (!$jobs) {
            return ['printing' => null, 'queue' => [], 'completed' => [], 'total' => 0];
        }

        $printing = null;
        $queue = [];
        $completed = [];

        // Pre-carica tutti gli ordini e fasi in 2 query (invece di N+1)
        $commesseCodes = array_unique(array_filter(array_column($jobs, 'commessa')));
        $ordiniMap = !empty($commesseCodes)
            ? Ordine::with('fasi')->whereIn('commessa', $commesseCodes)->get()->keyBy('commessa')
            : collect();

        foreach ($jobs as $job) {
            // Aggiungi info commessa dal MES
            $job['mes'] = null;
            if ($job['commessa'] && $ordiniMap->has($job['commessa'])) {
                $ordine = $ordiniMap[$job['commessa']];
                $fasi = $ordine->fasi->sortBy('priorita')->map(fn($f) => [
                    'fase' => $f->fase,
                    'stato' => $f->stato,
                    'qta_fase' => $f->qta_fase,
                    'qta_prod' => $f->qta_prod,
                    'esterno' => $f->esterno,
                ])->values()->toArray();

                $job['mes'] = [
                    'commessa' => $ordine->commessa,
                    'cliente' => $ordine->cliente_nome,
                    'cod_art' => $ordine->cod_art,
                    'descrizione' => $ordine->descrizione,
                    'qta_richiesta' => $ordine->qta_richiesta,
                    'qta_carta' => $ordine->qta_carta,
                    'cod_carta' => $ordine->cod_carta,
                    'carta' => $ordine->carta,
                    'data_prevista' => $ordine->data_prevista_consegna
                        ? (is_string($ordine->data_prevista_consegna)
                            ? date('d/m/Y', strtotime($ordine->data_prevista_consegna))
                            : $ordine->data_prevista_consegna->format('d/m/Y'))
                        : null,
                    'note_prestampa' => $ordine->note_prestampa,
                    'note_fasi' => $ordine->note_fasi_successive,
                    'responsabile' => $ordine->responsabile,
                    'fasi' => $fasi,
                ];
            }

            if ($job['state'] === 'printing') {
                $printing = $job;
            } elseif (in_array($job['state'], ['done spooling', 'waiting', 'processing', 'ripping'])) {
                $queue[] = $job;
            } elseif ($job['state'] === 'completed') {
                $completed[] = $job;
            }
        }

        // Completati: ultimi 15 (i più recenti per data)
        $completed = array_slice(array_reverse($completed), 0, 15);

        // Fogli totali per la commessa in stampa da Accounting (tutti i run storici)
        $commessaSheets = null;
        if ($printing && $printing['commessa']) {
            $printCommessa = $printing['commessa'];

            if ($accounting && isset($accounting[$printCommessa])) {
                $acc = $accounting[$printCommessa];
                $commessaSheets = [
                    'commessa' => $printCommessa,
                    'fogli_totali' => $acc['fogli'],
                    'copie_totali' => $acc['copie'],
                    'run_count' => $acc['run'],
                ];
            } else {
                // Fallback: somma dai job nella lista corrente
                $totalSheets = 0;
                $totalCopies = 0;
                $fileCount = 0;
                foreach ($jobs as $job) {
                    if ($job['commessa'] === $printCommessa && $job['total_sheets'] > 0) {
                        $totalSheets += $job['total_sheets'];
                        $totalCopies += $job['copies_printed'];
                        $fileCount++;
                    }
                }
                $commessaSheets = [
                    'commessa' => $printCommessa,
                    'fogli_totali' => $totalSheets,
                    'copie_totali' => $totalCopies,
                    'run_count' => $fileCount,
                ];
            }
        }

        return [
            'printing' => $printing,
            'queue' => $queue,
            'completed' => $completed,
            'total' => count($jobs),
            'commessa_sheets' => $commessaSheets,
        ];
    }

    /**
     * Diagnostica sync
     */
    public function debugSync(FieryService $fiery, FierySyncService $syncService)
    {
        $debug = [];

        $status = $fiery->getServerStatus();
        $debug['1_fiery_online'] = $status ? true : false;
        $debug['1_stato'] = $status['stato'] ?? 'N/A';

        if (!$status) {
            return response()->json($debug);
        }

        $jobName = $status['stampa']['documento'] ?? null;
        $debug['2_job_in_stampa'] = $jobName;

        $commessaCode = $syncService->estraiCommessa($jobName);
        $debug['3_commessa_estratta'] = $commessaCode;

        if (!$commessaCode) {
            $debug['3_errore'] = 'Nessun numero trovato nel nome job';
            return response()->json($debug);
        }

        $ordini = Ordine::where('commessa', $commessaCode)->get();
        $debug['4_ordini_count'] = $ordini->count();
        $debug['4_ordini'] = $ordini->map(function($o) use ($syncService) {
            return [
                'id' => $o->id,
                'cod_art' => $o->cod_art,
                'descrizione' => substr($o->descrizione, 0, 60),
                'cod_carta' => $o->cod_carta,
                'formato_digitale' => $syncService->isFormatoDigitale($o->cod_carta),
            ];
        })->toArray();

        if ($ordini->isEmpty()) {
            return response()->json($debug);
        }

        $nomeOp = config('fiery.operatore', 'Francesco Verde');
        $debug['5_operatore_config'] = $nomeOp;
        $parts = explode(' ', $nomeOp, 2);
        $operatore = \App\Models\Operatore::whereRaw('LOWER(nome) = ? AND LOWER(cognome) = ?', [
            strtolower($parts[0] ?? ''),
            strtolower($parts[1] ?? ''),
        ])->where('attivo', 1)->first();
        $debug['5_operatore_trovato'] = $operatore ? ($operatore->nome . ' ' . $operatore->cognome . ' (id=' . $operatore->id . ')') : 'NON TROVATO';

        $tutteFasiDigitali = collect();
        foreach ($ordini as $ordine) {
            $fasi = OrdineFase::where('ordine_id', $ordine->id)
                ->where(function ($q) use ($ordine, $syncService) {
                    $q->whereHas('faseCatalogo', function ($sub) {
                        $sub->where('reparto_id', 4);
                    });
                    if ($syncService->isFormatoDigitale($ordine->cod_carta)) {
                        $q->orWhere('fase', 'STAMPA');
                    }
                })
                ->get();
            $tutteFasiDigitali = $tutteFasiDigitali->merge($fasi);
        }
        $debug['6_fasi_digitali_count'] = $tutteFasiDigitali->count();
        $debug['6_fasi_digitali'] = $tutteFasiDigitali->map(function($f) {
            return [
                'id' => $f->id,
                'fase' => $f->fase,
                'stato' => $f->stato,
                'ordine_id' => $f->ordine_id,
            ];
        })->toArray();

        try {
            $risultato = $syncService->sincronizza();
            $debug['9_sync_risultato'] = $risultato;
        } catch (\Exception $e) {
            $debug['9_sync_errore'] = $e->getMessage();
        }

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Pagina contatori Canon iPR V900 — SNMP live + storico da DB
     */
    public function contatori(Request $request, FieryService $fiery)
    {
        set_time_limit(60);

        // SNMP: se stampante spenta, usa cache o array vuoto (non bloccare la pagina)
        $live = \Cache::remember('fiery_snmp_live', 30, fn() => $this->leggiContatoriSnmp());

        // Storico dal DB (sempre disponibile, non dipende dalla stampante)
        $storico = ContatoreStampante::where('stampante', 'Canon iPR V900')
            ->orderByDesc('rilevato_at')
            ->limit(52)
            ->get();

        $da = $request->get('da', now()->subDays(30)->format('Y-m-d'));
        $a = $request->get('a', now()->format('Y-m-d'));

        // Click per commessa dal DB (salvati dal cron fiery:sync)
        $clickPerCommessa = $this->getClickPerCommessaFromDb($da, $a);

        // Report mensile per categoria (usa snapshot DB, non dipende dalla stampante)
        $reportCategorie = $this->getReportCategorie($da, $a);

        return view('fiery.contatori', compact('live', 'storico', 'clickPerCommessa', 'da', 'a', 'reportCategorie'));
    }

    /**
     * Vista stampabile (HTML print-friendly) del solo report categorie.
     */
    public function reportCategoriePdf(Request $request, FieryService $fiery)
    {
        set_time_limit(180);
        $da = $request->get('da', now()->subDays(30)->format('Y-m-d'));
        $a = $request->get('a', now()->format('Y-m-d'));
        $reportCategorie = $this->getReportCategorie($da, $a);

        return view('fiery.report_categorie_pdf', compact('reportCategorie', 'da', 'a'));
    }

    /**
     * Report scatti per categoria nel periodo (formato fattura SAE).
     * Usa contatori SNMP storici (snapshot inizio/fine periodo).
     */
    private function getReportCategorie(string $da, string $a): array
    {
        $iniziale = ContatoreStampante::where('stampante', 'Canon iPR V900')
            ->whereDate('rilevato_at', '<=', $da)
            ->orderByDesc('rilevato_at')
            ->first();

        if (!$iniziale) {
            $iniziale = ContatoreStampante::where('stampante', 'Canon iPR V900')
                ->orderBy('rilevato_at')
                ->first();
        }

        $finale = ContatoreStampante::where('stampante', 'Canon iPR V900')
            ->whereDate('rilevato_at', '<=', $a)
            ->orderByDesc('rilevato_at')
            ->first();

        $report = [
            'bn_a4' => 0,
            'colore_a4' => 0,
            'bn_a3' => 0,
            'colore_a3' => 0,
            'banner' => 0,
            'totale' => 0,
            'lettura_iniziale_at' => $iniziale?->rilevato_at?->format('d/m/Y H:i'),
            'lettura_finale_at' => $finale?->rilevato_at?->format('d/m/Y H:i'),
        ];

        if (!$iniziale || !$finale || $iniziale->id === $finale->id) {
            return $report;
        }

        // Differenza tra letture (lati stampati / 2 = fogli)
        $report['bn_a4']     = max(0, (int) round(($finale->nero_piccolo   - $iniziale->nero_piccolo)   / 2));
        $report['colore_a4'] = max(0, (int) round(($finale->colore_piccolo - $iniziale->colore_piccolo) / 2));
        $report['bn_a3']     = max(0, (int) round(($finale->nero_grande    - $iniziale->nero_grande)    / 2));
        $report['colore_a3'] = max(0, (int) round(($finale->colore_grande  - $iniziale->colore_grande)  / 2));
        $report['banner']    = max(0, (int) round(($finale->foglio_lungo   - $iniziale->foglio_lungo)   / 2));

        $report['totale'] = $report['bn_a4'] + $report['colore_a4'] + $report['bn_a3'] + $report['colore_a3'] + $report['banner'];

        return $report;
    }

    /**
     * JSON contatori live per refresh AJAX
     */
    public function contatoriJson()
    {
        $data = \Cache::remember('fiery_snmp_live', 30, fn() => $this->leggiContatoriSnmp());
        return response()->json($data);
    }

    /**
     * Legge contatori via SNMP
     */
    private function leggiContatoriSnmp(): array
    {
        if (!function_exists('snmpget')) {
            return ['errore' => 'Estensione SNMP non abilitata', 'offline' => true];
        }

        $ip = config('fiery.host', '192.168.1.206');
        $community = 'public';
        // Timeout SNMP: 500ms (500000 microsec), 1 retry — se spenta fallisce in ~1s
        $snmpTimeout = 500000;
        $snmpRetries = 1;

        // Quick check: una sola chiamata SNMP per verificare se la stampante risponde
        $testVal = @snmpget($ip, $community, '.1.3.6.1.2.1.1.1.0', $snmpTimeout, $snmpRetries);
        if ($testVal === false) {
            return ['ip' => $ip, 'timestamp' => now()->format('d/m/Y H:i:s'), 'offline' => true];
        }

        $base = '.1.3.6.1.4.1.1602.1.11.1.3.1.4.';
        $oids = [
            101 => 'totale_1',
            112 => 'nero_grande',
            113 => 'nero_piccolo',
            122 => 'colore_grande',
            123 => 'colore_piccolo',
            501 => 'scansioni',
            471 => 'foglio_lungo',
        ];

        $result = ['ip' => $ip, 'timestamp' => now()->format('d/m/Y H:i:s'), 'offline' => false];

        foreach ($oids as $oid => $field) {
            $val = @snmpget($ip, $community, $base . $oid, $snmpTimeout, $snmpRetries);
            $result[$field] = $val !== false ? (int) preg_replace('/^.*:\s*/', '', $val) : null;
        }

        // Livelli toner (Printer MIB .43.11.1.1)
        $tonerBase = '.1.3.6.1.2.1.43.11.1.1.';
        $tonerNomi = [1 => 'Nero', 2 => 'Cyan', 3 => 'Magenta', 4 => 'Yellow', 5 => 'Waste Toner', 6 => 'ADF Kit'];
        $toner = [];
        foreach ($tonerNomi as $idx => $nome) {
            $livello = @snmpget($ip, $community, $tonerBase . '9.1.' . $idx, $snmpTimeout, $snmpRetries);
            $max = @snmpget($ip, $community, $tonerBase . '8.1.' . $idx, $snmpTimeout, $snmpRetries);
            if ($livello !== false && $max !== false) {
                $lv = (int) preg_replace('/^.*:\s*/', '', $livello);
                $mx = (int) preg_replace('/^.*:\s*/', '', $max);
                $toner[] = [
                    'nome' => $nome,
                    'livello' => $mx > 0 ? round(($lv / $mx) * 100) : $lv,
                    'raw' => $lv,
                    'max' => $mx,
                ];
            }
        }
        $result['toner'] = $toner;

        // Vassoi carta (Printer MIB .43.8.2.1)
        $vassoi = [];
        for ($i = 1; $i <= 5; $i++) {
            $nomeV = @snmpget($ip, $community, '.1.3.6.1.2.1.43.8.2.1.13.1.' . $i, $snmpTimeout, $snmpRetries);
            $capV = @snmpget($ip, $community, '.1.3.6.1.2.1.43.8.2.1.9.1.' . $i, $snmpTimeout, $snmpRetries);
            $livV = @snmpget($ip, $community, '.1.3.6.1.2.1.43.8.2.1.10.1.' . $i, $snmpTimeout, $snmpRetries);
            $tipoV = @snmpget($ip, $community, '.1.3.6.1.2.1.43.8.2.1.21.1.' . $i, $snmpTimeout, $snmpRetries);
            if ($nomeV !== false) {
                $cap = (int) preg_replace('/^.*:\s*/', '', $capV ?: '0');
                $liv = (int) preg_replace('/^.*:\s*/', '', $livV ?: '0');
                $nome = trim(preg_replace('/^.*:\s*"?|"$/s', '', $nomeV));
                $tipo = trim(preg_replace('/^.*:\s*"?|"$/s', '', $tipoV ?: ''));
                $pct = null;
                if ($liv >= 0 && $cap > 0) {
                    $pct = round(($liv / $cap) * 100);
                } elseif ($liv == -3) {
                    $pct = -1;
                }
                $vassoi[] = [
                    'nome' => $nome,
                    'capacita' => $cap,
                    'livello' => $liv,
                    'percentuale' => $pct,
                    'tipo' => $tipo,
                ];
            }
        }
        $result['vassoi'] = $vassoi;

        // Finisher - punti (Printer MIB .43.31.1.1)
        $punti = [];
        for ($i = 1; $i <= 2; $i++) {
            $nomeP = @snmpget($ip, $community, '.1.3.6.1.2.1.43.31.1.1.5.1.' . $i, $snmpTimeout, $snmpRetries);
            $maxP = @snmpget($ip, $community, '.1.3.6.1.2.1.43.31.1.1.7.1.' . $i, $snmpTimeout, $snmpRetries);
            $livP = @snmpget($ip, $community, '.1.3.6.1.2.1.43.31.1.1.8.1.' . $i, $snmpTimeout, $snmpRetries);
            if ($nomeP !== false) {
                $nome = trim(preg_replace('/^.*:\s*"?|"$/s', '', $nomeP));
                $mx = (int) preg_replace('/^.*:\s*/', '', $maxP ?: '0');
                $lv = (int) preg_replace('/^.*:\s*/', '', $livP ?: '0');
                $punti[] = [
                    'nome' => $nome,
                    'livello' => $mx > 0 ? round(($lv / $mx) * 100) : $lv,
                ];
            }
        }
        $result['punti'] = $punti;

        // Alert attivo
        $alert = @snmpget($ip, $community, '.1.3.6.1.2.1.43.16.5.1.2.1.1', $snmpTimeout, $snmpRetries);
        $result['alert'] = $alert !== false ? trim(preg_replace('/^.*:\s*"?|"$/s', '', $alert)) : null;

        return $result;
    }

    /**
     * Click per commessa dal DB (dati salvati dal cron fiery:sync)
     */
    private function getClickPerCommessaFromDb(string $da, string $a): array
    {
        $rows = \App\Models\FieryAccounting::whereBetween('data_stampa', [$da, $a])
            ->get();

        if ($rows->isEmpty()) return [];

        $perCommessa = [];
        foreach ($rows as $row) {
            $key = $row->commessa ?: '__senza_commessa__';

            if (!isset($perCommessa[$key])) {
                $ordine = $key !== '__senza_commessa__' ? Ordine::where('commessa', $key)->first() : null;
                $perCommessa[$key] = [
                    'commessa' => $key === '__senza_commessa__' ? '(Senza commessa)' : $key,
                    'cliente' => $ordine->cliente_nome ?? '',
                    'descrizione' => $ordine ? \Illuminate\Support\Str::limit($ordine->descrizione ?? '', 60) : ($key === '__senza_commessa__' ? 'Test, calibrazione, prove colore' : ''),
                    'fogli' => 0,
                    'colore' => 0,
                    'bn' => 0,
                    'copie' => 0,
                    'run' => 0,
                    'fogli_grande' => 0,
                    'fogli_piccolo' => 0,
                    'formati' => [],
                ];
            }

            $perCommessa[$key]['fogli'] += $row->fogli;
            $perCommessa[$key]['colore'] += $row->pagine_colore;
            $perCommessa[$key]['bn'] += $row->pagine_bn;
            $perCommessa[$key]['copie'] += $row->copie;
            $perCommessa[$key]['run']++;
            $perCommessa[$key]['fogli_' . $row->tipo_formato] += $row->fogli;
            if ($row->formato && !in_array($row->formato, $perCommessa[$key]['formati'])) {
                $perCommessa[$key]['formati'][] = $row->formato;
            }
        }

        usort($perCommessa, fn($a, $b) => $b['fogli'] - $a['fogli']);

        return $perCommessa;
    }

    /**
     * Click (fogli/pagine colore/BN) per commessa dal Fiery Accounting API, filtrati per data
     */
    private function getClickPerCommessa(FieryService $fiery, string $da, string $a): array
    {
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;

        try {
            $loginR = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(15)
                ->post($baseUrl . '/live/api/v5/login', [
                    'username' => config('fiery.username'),
                    'password' => config('fiery.password'),
                    'accessrights' => config('fiery.api_key'),
                ]);

            if (!$loginR->successful()) return [];

            $cookies = [];
            foreach ($loginR->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            $r = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(60)
                ->withCookies($cookies, $host)
                ->get($baseUrl . '/live/api/v5/accounting');

            // Logout
            try {
                \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->withCookies($cookies, $host)
                    ->post($baseUrl . '/live/api/v5/logout');
            } catch (\Exception $e) {}

            if (!$r->successful()) return [];

            $json = $r->json();
            $items = $json['data']['items'] ?? $json;
            if (!is_array($items)) return [];

            $daTs = strtotime($da);
            $aTs = strtotime($a . ' 23:59:59');

            $perCommessa = [];
            foreach ($items as $entry) {
                // Parsa data
                $dateStr = $entry['date'] ?? '';
                $ts = $this->parseFieryDate($dateStr);
                if (!$ts || $ts < $daTs || $ts > $aTs) continue;

                $title = $entry['title'] ?? '';
                $commessa = $fiery->estraiCommessaDaTitolo($title) ?: '__senza_commessa__';

                $fogli = (int) ($entry['total sheets printed'] ?? 0);
                $colore = (int) ($entry['total color pages printed'] ?? 0);
                $bn = (int) ($entry['total bw pages printed'] ?? 0);
                $copie = (int) ($entry['copies printed'] ?? 0);
                $mediaSize = $entry['media size'] ?? '';

                // Classifica Grande/Piccolo dal formato
                // Grande = lato maggiore > 297mm (oltre A3)
                $tipo = 'piccolo';
                if (preg_match('/\((\d+)[\.,]\d+\s*x\s*(\d+)[\.,]\d+/', $mediaSize, $dimM)) {
                    $maxDim = max((int) $dimM[1], (int) $dimM[2]);
                    $tipo = $maxDim > 297 ? 'grande' : 'piccolo';
                } elseif (preg_match('/^(\d+)\s*x\s*(\d+)/', $mediaSize, $dimM)) {
                    $maxDim = max((int) $dimM[1], (int) $dimM[2]);
                    $tipo = $maxDim > 297 ? 'grande' : 'piccolo';
                } elseif (preg_match('/SRA3|A3/i', $mediaSize)) {
                    $tipo = 'grande';
                } elseif (preg_match('/A4|A5/i', $mediaSize)) {
                    $tipo = 'piccolo';
                }

                if (!isset($perCommessa[$commessa])) {
                    // Cerca nel MES
                    $ordine = $commessa !== '__senza_commessa__' ? Ordine::where('commessa', $commessa)->first() : null;
                    $perCommessa[$commessa] = [
                        'commessa' => $commessa === '__senza_commessa__' ? '(Senza commessa)' : $commessa,
                        'cliente' => $ordine->cliente_nome ?? '',
                        'descrizione' => $ordine ? \Illuminate\Support\Str::limit($ordine->descrizione ?? '', 60) : ($commessa === '__senza_commessa__' ? 'Test, calibrazione, prove colore' : ''),
                        'fogli' => 0,
                        'colore' => 0,
                        'bn' => 0,
                        'copie' => 0,
                        'run' => 0,
                        'fogli_grande' => 0,
                        'fogli_piccolo' => 0,
                        'formati' => [],
                    ];
                }

                $perCommessa[$commessa]['fogli'] += $fogli;
                $perCommessa[$commessa]['colore'] += $colore;
                $perCommessa[$commessa]['bn'] += $bn;
                $perCommessa[$commessa]['copie'] += $copie;
                $perCommessa[$commessa]['run']++;
                $perCommessa[$commessa]['fogli_' . $tipo] += $fogli;
                if ($mediaSize && !in_array($mediaSize, $perCommessa[$commessa]['formati'])) {
                    $perCommessa[$commessa]['formati'][] = $mediaSize;
                }
            }

            // Ordina per fogli decrescenti
            usort($perCommessa, fn($a, $b) => $b['fogli'] - $a['fogli']);

            return $perCommessa;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Parsa date in vari formati Fiery
     */
    private function parseFieryDate(string $dateStr): ?int
    {
        if (empty($dateStr)) return null;
        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'm/d/Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $dateStr);
            if ($d) return $d->getTimestamp();
        }
        $ts = strtotime($dateStr);
        return $ts !== false ? $ts : null;
    }

    /**
     * Estrae il numero dal nome job e cerca la commessa nel MES.
     */
    private function cercaCommessa(?string $jobName): ?array
    {
        if (!$jobName) return null;

        if (!preg_match('/^(\d+)[\s_]/', $jobName, $matches)) {
            return null;
        }

        $numero = $matches[1];
        $commessaCode = '00' . $numero . '-26';

        $ordine = Ordine::where('commessa', $commessaCode)->first();
        if (!$ordine) return null;

        $fasiAttive = OrdineFase::where('ordine_id', $ordine->id)
            ->where('stato', 2)
            ->with(['operatori' => function($q) {
                $q->select('operatori.id', 'operatori.nome');
            }])
            ->get();

        $operatori = $fasiAttive->flatMap(function($fase) {
            return $fase->operatori->pluck('nome');
        })->unique()->values()->toArray();

        return [
            'commessa' => $ordine->commessa,
            'cliente' => $ordine->cliente_nome,
            'descrizione' => $ordine->descrizione,
            'operatori' => $operatori,
        ];
    }
}
