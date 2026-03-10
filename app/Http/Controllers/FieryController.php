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
        $status = $fiery->getServerStatus();

        if ($status) {
            try {
                $syncService->sincronizza();
            } catch (\Exception $e) {
                // Non bloccare la dashboard se il sync fallisce
            }
        }

        if ($status) {
            $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);
        }

        // Job list da API v5
        $jobs = $fiery->getJobs();
        $accounting = $fiery->getAccountingPerCommessa();
        $jobData = $this->organizzaJobs($jobs, $accounting);

        return view('fiery.dashboard', compact('status', 'jobData'));
    }

    public function statusJson(FieryService $fiery, FierySyncService $syncService)
    {
        $status = $fiery->getServerStatus();

        if (!$status) {
            return response()->json([
                'online' => false,
                'stato' => 'offline',
                'avviso' => 'Fiery non raggiungibile',
            ]);
        }

        try {
            $syncService->sincronizza();
        } catch (\Exception $e) {
            // Non bloccare il polling
        }

        $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);

        // Job list da API v5
        $fieryService = app(FieryService::class);
        $jobs = $fieryService->getJobs();
        $accounting = $fieryService->getAccountingPerCommessa();
        $status['jobs'] = $this->organizzaJobs($jobs, $accounting);

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

        foreach ($jobs as $job) {
            // Aggiungi info commessa dal MES
            $job['mes'] = null;
            if ($job['commessa']) {
                $ordine = Ordine::where('commessa', $job['commessa'])->first();
                if ($ordine) {
                    // Fasi della commessa
                    $fasi = OrdineFase::where('ordine_id', $ordine->id)
                        ->orderBy('priorita')
                        ->get()
                        ->map(fn($f) => [
                            'fase' => $f->fase,
                            'stato' => $f->stato,
                            'qta_fase' => $f->qta_fase,
                            'qta_prod' => $f->qta_prod,
                            'esterno' => $f->esterno,
                        ])->toArray();

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
        // Lettura live SNMP
        $live = $this->leggiContatoriSnmp();

        // Storico dal DB
        $storico = ContatoreStampante::where('stampante', 'Canon iPR V900')
            ->orderByDesc('rilevato_at')
            ->limit(52)
            ->get();

        // Click per commessa da Accounting API
        $da = $request->get('da', now()->subDays(30)->format('Y-m-d'));
        $a = $request->get('a', now()->format('Y-m-d'));
        $clickPerCommessa = $this->getClickPerCommessa($fiery, $da, $a);

        return view('fiery.contatori', compact('live', 'storico', 'clickPerCommessa', 'da', 'a'));
    }

    /**
     * JSON contatori live per refresh AJAX
     */
    public function contatoriJson()
    {
        return response()->json($this->leggiContatoriSnmp());
    }

    /**
     * Legge contatori via SNMP
     */
    private function leggiContatoriSnmp(): array
    {
        if (!function_exists('snmpget')) {
            return ['errore' => 'Estensione SNMP non abilitata'];
        }

        $ip = config('fiery.host', '192.168.1.206');
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

        $result = ['ip' => $ip, 'timestamp' => now()->format('d/m/Y H:i:s')];

        foreach ($oids as $oid => $field) {
            $val = @snmpget($ip, 'public', $base . $oid);
            $result[$field] = $val !== false ? (int) preg_replace('/^.*:\s*/', '', $val) : null;
        }

        return $result;
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
                $commessa = $fiery->estraiCommessaDaTitolo($title);
                if (!$commessa) continue;

                $fogli = (int) ($entry['total sheets printed'] ?? 0);
                $colore = (int) ($entry['total color pages printed'] ?? 0);
                $bn = (int) ($entry['total bw pages printed'] ?? 0);
                $copie = (int) ($entry['copies printed'] ?? 0);
                $mediaSize = $entry['media size'] ?? '';

                if (!isset($perCommessa[$commessa])) {
                    // Cerca nel MES
                    $ordine = Ordine::where('commessa', $commessa)->first();
                    $perCommessa[$commessa] = [
                        'commessa' => $commessa,
                        'cliente' => $ordine->cliente_nome ?? '',
                        'descrizione' => $ordine ? \Illuminate\Support\Str::limit($ordine->descrizione ?? '', 60) : '',
                        'fogli' => 0,
                        'colore' => 0,
                        'bn' => 0,
                        'copie' => 0,
                        'run' => 0,
                        'formati' => [],
                    ];
                }

                $perCommessa[$commessa]['fogli'] += $fogli;
                $perCommessa[$commessa]['colore'] += $colore;
                $perCommessa[$commessa]['bn'] += $bn;
                $perCommessa[$commessa]['copie'] += $copie;
                $perCommessa[$commessa]['run']++;
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
