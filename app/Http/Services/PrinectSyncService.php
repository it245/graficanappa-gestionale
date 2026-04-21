<?php

namespace App\Http\Services;

use App\Models\PrinectAttivita;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\Reparto;
use Carbon\Carbon;
use App\Services\FaseStatoService;

class PrinectSyncService
{
    protected PrinectService $prinect;

    public function __construct(PrinectService $prinect)
    {
        $this->prinect = $prinect;
    }

    public function sincronizzaAttivita(): int
    {
        $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');

        // Fetch ultimi 30 giorni di attivita per catturare anche job completati in passato
        $start = Carbon::now()->subDays(30)->format('Y-m-d\TH:i:sP');
        $end = Carbon::now()->format('Y-m-d\TH:i:sP');
        $data = $this->prinect->getDeviceActivity($deviceId, $start, $end);

        if (!$data || !isset($data['activities'])) {
            return 0;
        }

        $importate = 0;
        $commesseAggiornate = [];
        $cacheAnnoJob = []; // Cache anno per job_id (evita chiamate API ripetute)

        foreach ($data['activities'] as $att) {
            // Filtra attivita "marker" con id=null (durata 0, nessun dato utile)
            if (empty($att['id'])) continue;

            $startTime = isset($att['startTime']) ? date('Y-m-d H:i:s', strtotime($att['startTime'])) : null;
            $endTime = isset($att['endTime']) ? date('Y-m-d H:i:s', strtotime($att['endTime'])) : null;

            if (!$startTime) continue;

            // Dedup: skip se esiste gia' con stesso device_id + start_time + activity_id
            $esiste = PrinectAttivita::where('device_id', $deviceId)
                ->where('start_time', $startTime)
                ->where('activity_id', $att['id'])
                ->exists();

            if ($esiste) continue;

            // Estrai commessa dal job_id con anno corretto da creationDate
            $rawJobId = $att['workstep']['job']['id'] ?? null;
            $jobId = self::estraiJobIdNumerico($rawJobId);
            $commessa = null;
            if ($jobId) {
                $anno = $this->getAnnoJob($jobId, $att['startTime'], $cacheAnnoJob);
                $commessa = str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . $anno;
            }

            // Operatori: unisci nomi
            $operatori = '';
            if (!empty($att['employees'])) {
                $nomi = array_map(function ($e) {
                    return trim(($e['firstName'] ?? '') . ' ' . ($e['name'] ?? ''));
                }, $att['employees']);
                $operatori = implode(', ', $nomi);
            }

            PrinectAttivita::create([
                'device_id'            => $att['device']['id'] ?? $deviceId,
                'device_name'          => $att['device']['name'] ?? null,
                'activity_id'          => $att['id'],
                'activity_name'        => $att['name'] ?? null,
                'time_type_name'       => $att['timeTypeName'] ?? null,
                'time_type_group'      => $att['timeTypeGroupName'] ?? null,
                'prinect_job_id'       => $jobId,
                'prinect_job_name'     => $att['workstep']['job']['name'] ?? null,
                'commessa_gestionale'  => $commessa,
                'workstep_name'        => $att['workstep']['name'] ?? null,
                'good_cycles'          => $att['goodCycles'] ?? 0,
                'waste_cycles'         => $att['wasteCycles'] ?? 0,
                'start_time'           => $startTime,
                'end_time'             => $endTime,
                'operatore_prinect'    => $operatori ?: null,
                'cost_center'          => $att['costCenter'] ?? null,
            ]);

            $importate++;

            if ($commessa) {
                $commesseAggiornate[$commessa] = true;
            }
        }

        // Aggiorna fogli_buoni/scarto sulle fasi di stampa offset per ogni commessa toccata
        foreach (array_keys($commesseAggiornate) as $commessa) {
            $this->aggiornaFogliCommessa($commessa);
        }

        // Aggiorna anche commesse con attivita già in DB ma fogli non ancora sincronizzati
        $commesseNonSincronizzate = PrinectAttivita::whereNotNull('commessa_gestionale')
            ->where('good_cycles', '>', 0)
            ->distinct()
            ->pluck('commessa_gestionale');

        foreach ($commesseNonSincronizzate as $commessa) {
            if (isset($commesseAggiornate[$commessa])) continue;

            $fasi = $this->troveFasiStampa($commessa);
            if ($fasi->isNotEmpty() && $fasi->contains(fn($f) => ($f->qta_prod ?? 0) == 0)) {
                $this->aggiornaFogliCommessa($commessa);
            }
        }

        // Controlla auto-terminazione per fasi con produzione ma non ancora terminate
        $fasiDaControllare = OrdineFase::where('qta_prod', '>', 0)
            ->where('stato', '<', 3)
            ->pluck('id');

        foreach ($fasiDaControllare as $faseId) {
            FaseStatoService::controllaCompletamento($faseId);
        }

        // Auto-terminazione via stato workstep Prinect (COMPLETED)
        $this->controllaCompletamentoPrinect();

        // Ripristina fasi erroneamente terminate che hanno attività recenti o workstep non COMPLETED
        $this->ripristinaFasiAttive();

        // Termina fasi stampa "abbandonate": stato 2 ma nessuna attività recente
        $this->terminaFasiAbbandonate();

        return $importate;
    }

    /**
     * Determina l'anno della commessa per un job Prinect.
     * Prima prova dal DB gestionale, poi dalla creationDate API, infine fallback su startTime.
     */
    protected function getAnnoJob(string $jobId, string $startTime, array &$cache): string
    {
        if (isset($cache[$jobId])) {
            return $cache[$jobId];
        }

        // 1. Cerca nel gestionale: commessa che contiene il jobId
        $padded = str_pad($jobId, 7, '0', STR_PAD_LEFT);
        $ordine = Ordine::where('commessa', 'LIKE', $padded . '-%')->first();
        if ($ordine) {
            $anno = substr($ordine->commessa, -2);
            $cache[$jobId] = $anno;
            return $anno;
        }

        // 2. Prova creationDate dalla API Prinect (con cache)
        try {
            $jobData = $this->prinect->getJob($jobId);
            if ($jobData && isset($jobData['job']['creationDate'])) {
                $anno = date('y', strtotime($jobData['job']['creationDate']));
                $cache[$jobId] = $anno;
                return $anno;
            }
        } catch (\Exception $e) {
            // Ignora errori API, usa fallback
        }

        // 3. Fallback: anno dall'attivita startTime
        $anno = date('y', strtotime($startTime));
        $cache[$jobId] = $anno;
        return $anno;
    }

    /**
     * Aggiorna fogli, tempi, stato, data_inizio e operatore sulle fasi di stampa offset
     * della commessa dal totale Prinect (dati dal DB prinect_attivita).
     * Con fasi multiple, distribuisce le attivita per prefisso workstep.
     */
    protected function aggiornaFogliCommessa(string $commessa): void
    {
        $fasi = $this->troveFasiStampa($commessa);
        if ($fasi->isEmpty()) return;

        $attivita = PrinectAttivita::where('commessa_gestionale', $commessa)->get();
        if ($attivita->isEmpty()) return;

        // Operatori Prinect unici
        $operatoriPrinect = $attivita
            ->whereNotNull('operatore_prinect')
            ->where('operatore_prinect', '!=', '')
            ->pluck('operatore_prinect')
            ->unique()
            ->flatMap(function ($nomiStr) {
                return collect(explode(',', $nomiStr))->map(function ($nomeCompleto) {
                    $parts = explode(' ', trim($nomeCompleto));
                    return [
                        'nome' => count($parts) > 1 ? $parts[0] : null,
                        'cognome' => end($parts),
                    ];
                });
            })
            ->filter(fn($p) => !empty($p['cognome']))
            ->values();

        $operatoriMatched = $this->matchOperatori($operatoriPrinect->toArray());

        // Se ci sono fasi duplicate (es. max 2 fasi), split attività per tempo
        // usando il data_fine della fase terminata come cutoff
        $fasiTerminate = $fasi->filter(fn($f) => $f->stato >= 3 && $f->data_fine);
        $fasiAttive = $fasi->filter(fn($f) => $f->stato < 3);

        if ($fasiTerminate->isNotEmpty() && $fasiAttive->isNotEmpty() && $fasi->count() > 1) {
            // Trova il cutoff: data_fine più recente tra le fasi terminate
            $cutoff = $fasiTerminate->max(fn($f) => Carbon::parse($f->data_fine));
            $cutoff = Carbon::parse($cutoff);

            // Attività prima del cutoff → fasi terminate (ordinate per id)
            $attPrima = $attivita->filter(fn($a) => $a->end_time && Carbon::parse($a->end_time)->lte($cutoff));
            // Attività dopo il cutoff (o in corso) → fasi attive
            $attDopo = $attivita->filter(fn($a) => !$a->end_time || Carbon::parse($a->end_time)->gt($cutoff));

            // Aggiorna fasi terminate con le loro attività
            foreach ($fasiTerminate as $fase) {
                $this->aggiornaFaseConAttivita($fase, $attPrima, $operatoriMatched);
            }

            // Aggiorna fasi attive con le attività successive
            foreach ($fasiAttive as $fase) {
                $this->aggiornaFaseConAttivita($fase, $attDopo, $operatoriMatched);
            }
        } else {
            // Distribuzione standard per prefisso workstep
            $attivitaPerFase = $this->distribuisciAttivitaTraFasi(
                $attivita, $fasi, fn($a) => $a->workstep_name ?? ''
            );

            foreach ($fasi as $fase) {
                $att = collect($attivitaPerFase[$fase->id] ?? []);
                if ($att->isEmpty()) continue;
                $this->aggiornaFaseConAttivita($fase, $att, $operatoriMatched);
            }
        }
    }

    /**
     * Sincronizza da dati LIVE API Prinect: salva in prinect_attivita + aggiorna fasi.
     * Chiamato dalla dashboard per aggiornamento real-time.
     */
    public function sincronizzaDaLive(array $rawActivities): void
    {
        $cache = [];
        $perCommessa = [];
        $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');

        foreach ($rawActivities as $att) {
            if (empty($att['id'])) continue;

            $rawJobId = $att['workstep']['job']['id'] ?? null;
            $jobId = self::estraiJobIdNumerico($rawJobId);
            if (!$jobId) continue;

            $startTime = isset($att['startTime']) ? date('Y-m-d H:i:s', strtotime($att['startTime'])) : null;
            $endTime = isset($att['endTime']) ? date('Y-m-d H:i:s', strtotime($att['endTime'])) : null;
            if (!$startTime) continue;

            $anno = $this->getAnnoJob($jobId, $att['startTime'] ?? '', $cache);
            $commessa = str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . $anno;

            $perCommessa[$commessa][] = $att;

            // Salva in prinect_attivita (dedup)
            $esiste = PrinectAttivita::where('device_id', $deviceId)
                ->where('start_time', $startTime)
                ->where('activity_id', $att['id'])
                ->exists();

            if (!$esiste) {
                $operatori = '';
                if (!empty($att['employees'])) {
                    $nomi = array_map(fn($e) => trim(($e['firstName'] ?? '') . ' ' . ($e['name'] ?? '')), $att['employees']);
                    $operatori = implode(', ', $nomi);
                }

                PrinectAttivita::create([
                    'device_id'            => $att['device']['id'] ?? $deviceId,
                    'device_name'          => $att['device']['name'] ?? null,
                    'activity_id'          => $att['id'],
                    'activity_name'        => $att['name'] ?? null,
                    'time_type_name'       => $att['timeTypeName'] ?? null,
                    'time_type_group'      => $att['timeTypeGroupName'] ?? null,
                    'prinect_job_id'       => $jobId,
                    'prinect_job_name'     => $att['workstep']['job']['name'] ?? null,
                    'commessa_gestionale'  => $commessa,
                    'workstep_name'        => $att['workstep']['name'] ?? null,
                    'good_cycles'          => $att['goodCycles'] ?? 0,
                    'waste_cycles'         => $att['wasteCycles'] ?? 0,
                    'start_time'           => $startTime,
                    'end_time'             => $endTime,
                    'operatore_prinect'    => $operatori ?: null,
                    'cost_center'          => $att['costCenter'] ?? null,
                ]);
            }
        }

        // Ordina le commesse per ultima attivita (chi ha l'attivita piu recente e quella corrente)
        $ultimaAttivitaPerCommessa = [];
        foreach ($perCommessa as $commessa => $attivitaList) {
            $maxStart = null;
            $maxEnd = null;
            foreach ($attivitaList as $a) {
                $st = $a['startTime'] ?? null;
                $en = $a['endTime'] ?? null;
                if ($st && (!$maxStart || $st > $maxStart)) $maxStart = $st;
                if ($en && (!$maxEnd || $en > $maxEnd)) $maxEnd = $en;
            }
            $ultimaAttivitaPerCommessa[$commessa] = [
                'lastStart' => $maxStart,
                'lastEnd' => $maxEnd,
            ];
        }

        // La commessa con l'attivita piu recente e quella corrente (ancora in corso)
        uasort($ultimaAttivitaPerCommessa, fn($a, $b) => ($a['lastStart'] ?? '') <=> ($b['lastStart'] ?? ''));
        $commesseOrdinate = array_keys($ultimaAttivitaPerCommessa);
        $commessaCorrente = end($commesseOrdinate);

        // Aggiorna fasi di stampa per ogni commessa
        foreach ($perCommessa as $commessa => $attivitaList) {
            $attColl = collect($attivitaList);
            $haProduzione = $attColl->contains(fn($a) => ($a['name'] ?? '') !== 'Avviamento');

            // Se l'unica attività è un avviamento (richiamo mattutino della sera prima),
            // non considerare questa commessa come "corrente" — è solo un richiamo
            $soloAvviamento = !$haProduzione;
            $eCorrente = ($commessa === $commessaCorrente && count($perCommessa) > 1);

            // Terminata se: non è corrente, oppure è corrente ma ha solo avviamenti (richiamo)
            $terminata = (!$eCorrente || $soloAvviamento) && $haProduzione;
            $dataFine = $terminata ? ($ultimaAttivitaPerCommessa[$commessa]['lastEnd'] ?? null) : null;
            $this->aggiornaFaseStampaDaApi($commessa, $attivitaList, $terminata, $dataFine);
        }
    }

    /**
     * Aggiorna fasi di stampa offset per una commessa con dati raw dalla API.
     * $terminata: true se l'operatore ha gia iniziato un'altra commessa dopo questa
     * $dataFineOverride: data_fine da impostare se terminata
     */
    protected function aggiornaFaseStampaDaApi(string $commessa, array $attivitaApi, bool $terminata = false, ?string $dataFineOverride = null): void
    {
        $fasi = $this->troveFasiStampa($commessa);
        if ($fasi->isEmpty()) return;

        // Estrai nome+cognome operatori dalle attivita API
        $operatoriPrinect = [];
        $seen = [];
        foreach ($attivitaApi as $a) {
            foreach ($a['employees'] ?? [] as $e) {
                $nome = trim($e['firstName'] ?? '');
                $cognome = trim($e['name'] ?? '');
                $key = strtolower($nome . ' ' . $cognome);
                if ($cognome && !isset($seen[$key])) {
                    $seen[$key] = true;
                    $operatoriPrinect[] = ['nome' => $nome ?: null, 'cognome' => $cognome];
                }
            }
        }

        $operatoriMatched = $this->matchOperatori($operatoriPrinect);

        $dataFine = null;
        if ($terminata && $dataFineOverride) {
            $dataFine = Carbon::parse($dataFineOverride);
        }

        // Distribuisci attivita tra le fasi per prefisso workstep
        $attivitaPerFase = $this->distribuisciAttivitaTraFasi(
            $attivitaApi, $fasi, fn($a) => $a['workstep']['name'] ?? ''
        );

        foreach ($fasi as $fase) {
            $att = $attivitaPerFase[$fase->id] ?? [];
            if (empty($att)) continue;

            $buoni = 0;
            $scarto = 0;
            $secAvv = 0;
            $secProd = 0;

            foreach ($att as $a) {
                $buoni += $a['goodCycles'] ?? 0;
                $scarto += $a['wasteCycles'] ?? 0;

                if (isset($a['startTime'], $a['endTime'])) {
                    $diff = Carbon::parse($a['startTime'])->diffInSeconds(Carbon::parse($a['endTime']));
                    if (($a['name'] ?? '') === 'Avviamento') {
                        $secAvv += $diff;
                    } else {
                        $secProd += $diff;
                    }
                }
            }

            $sorted = collect($att)->filter(fn($a) => isset($a['startTime']))->sortBy('startTime');
            $dataInizio = $sorted->isNotEmpty()
                ? Carbon::parse($sorted->first()['startTime'])
                : null;

            $this->aggiornaFasi(collect([$fase]), [
                'fogli_buoni' => $buoni,
                'fogli_scarto' => $scarto,
                'tempo_avviamento_sec' => $secAvv,
                'tempo_esecuzione_sec' => $secProd,
            ], $dataInizio, $operatoriMatched, $terminata, $dataFine);
        }
    }

    /**
     * Helper: calcola dati da attività Prinect e aggiorna una fase.
     */
    protected function aggiornaFaseConAttivita($fase, $att, array $operatoriMatched): void
    {
        $att = collect($att);
        if ($att->isEmpty()) return;

        $secAvviamento = 0;
        $secProduzione = 0;
        foreach ($att as $a) {
            if (!$a->start_time || !$a->end_time) continue;
            $diff = $a->start_time->diffInSeconds($a->end_time);
            if ($a->activity_name === 'Avviamento') {
                $secAvviamento += $diff;
            } else {
                $secProduzione += $diff;
            }
        }

        $primaAtt = $att->filter(fn($a) => $a->start_time)->sortBy('start_time')->first();

        $this->aggiornaFasi(collect([$fase]), [
            'fogli_buoni' => $att->sum('good_cycles'),
            'fogli_scarto' => $att->sum('waste_cycles'),
            'tempo_avviamento_sec' => $secAvviamento,
            'tempo_esecuzione_sec' => $secProduzione,
        ], $primaAtt?->start_time, $operatoriMatched);
    }

    /**
     * Trova le fasi di stampa offset per una commessa (STAMPAXL106* o STAMPA).
     */
    protected function troveFasiStampa(string $commessa)
    {
        return OrdineFase::with('ordine')
            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->where(function ($q) {
                $q->where('fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('fase', 'STAMPA')
                  ->orWhere('fase', 'LIKE', 'STAMPA XL%');
            })
            ->get();
    }

    /**
     * Aggiorna le fasi con fogli, tempi, stato, data_inizio e operatori.
     * Stato 2 = Avviato (come da dashboard operatore)
     * Stato 3 = Terminato (quando l'operatore avvia un'altra commessa)
     */
    protected function aggiornaFasi($fasi, array $dati, $dataInizio, array $operatoriMatched, bool $terminata = false, $dataFine = null): void
    {
        if ($fasi->isEmpty()) return;

        $primoOperatore = $operatoriMatched[0] ?? null;

        foreach ($fasi as $fase) {
            $fase->fogli_buoni = $dati['fogli_buoni'];
            $fase->fogli_scarto = $dati['fogli_scarto'];
            $fase->qta_prod = $dati['fogli_buoni'];
            $fase->tempo_avviamento_sec = $dati['tempo_avviamento_sec'];
            $fase->tempo_esecuzione_sec = $dati['tempo_esecuzione_sec'];

            // Se la fase non e ancora avviata (stato 0 o 1), avviala (stato=2)
            // Ma solo se ha fogli buoni o non è stata riportata manualmente a 0
            if (in_array($fase->stato, [0, '0', 1, '1']) && $dati['fogli_buoni'] > 0) {
                $fase->stato = 2;
            }

            // Se la fase è terminata (stato 3) ma il job è RUNNING → recupera a stato 2
            // Skip se terminata manualmente dall'owner
            if (in_array($fase->stato, [3, '3']) && !$terminata && !$fase->terminata_manualmente) {
                $fase->stato = 2;
                $fase->data_fine = null;
            }

            // Se l'operatore ha avviato un'altra commessa → questa e terminata
            if ($terminata && !in_array($fase->stato, [3, '3'])) {
                $fase->stato = 3;
                if (!$fase->data_fine && $dataFine) {
                    $fase->data_fine = $dataFine;
                }
            }

            // Imposta data_inizio: usa la piu vecchia tra DB e Prinect
            if ($dataInizio) {
                if (!$fase->data_inizio || Carbon::parse($fase->data_inizio) > Carbon::parse($dataInizio)) {
                    $fase->data_inizio = $dataInizio;
                }
            }

            // Imposta operatore_id se mancante
            if (!$fase->operatore_id && $primoOperatore) {
                $fase->operatore_id = $primoOperatore->id;
            }

            $fase->save();

            // Controlla auto-terminazione via fogli_buoni + scarti_previsti
            FaseStatoService::controllaCompletamento($fase->id);

            // Aggiungi tutti gli operatori alla pivot se non gia presenti
            foreach ($operatoriMatched as $op) {
                if (!$fase->operatori()->where('operatore_id', $op->id)->exists()) {
                    $fase->operatori()->attach($op->id, [
                        'data_inizio' => $dataInizio,
                        'data_fine' => $terminata ? $dataFine : null,
                    ]);
                } elseif ($terminata && $dataFine) {
                    // Aggiorna data_fine sulla pivot se la fase e terminata
                    $fase->operatori()->updateExistingPivot($op->id, [
                        'data_fine' => $dataFine,
                    ]);
                }
            }
        }
    }

    /**
     * Distribuisce le attivita Prinect tra le fasi in base al prefisso workstep.
     * Es. workstep "copertina_FB 001 4/0" → fase con ordine che contiene "copertina".
     * Se c'e una sola fase, tutte le attivita vanno ad essa.
     * Supporta N fasi: ogni prefisso viene matchato alla fase il cui ordine->descrizione lo contiene.
     */
    protected function distribuisciAttivitaTraFasi($attivita, $fasi, callable $getWorkstepName): array
    {
        $result = [];

        if ($fasi->count() === 1) {
            $result[$fasi->first()->id] = is_array($attivita) ? $attivita : $attivita->all();
            return $result;
        }

        // Raggruppa per prefisso workstep
        $gruppi = [];
        foreach ($attivita as $att) {
            $prefix = $this->estraiPrefissoWorkstep($getWorkstepName($att));
            $gruppi[$prefix][] = $att;
        }

        // Match prefisso → fase via descrizione ordine
        $prefissiUsati = [];
        foreach ($fasi as $fase) {
            $desc = strtolower($fase->ordine->descrizione ?? '');
            foreach ($gruppi as $prefix => $attList) {
                if ($prefix === '' || in_array($prefix, $prefissiUsati)) continue;
                if (str_contains($desc, $prefix)) {
                    $result[$fase->id] = array_merge($result[$fase->id] ?? [], $attList);
                    $prefissiUsati[] = $prefix;
                }
            }
        }

        // Prefissi non matchati → fasi senza match (o prima fase come fallback)
        $rimasti = [];
        foreach ($gruppi as $prefix => $attList) {
            if ($prefix === '' || !in_array($prefix, $prefissiUsati)) {
                $rimasti = array_merge($rimasti, $attList);
            }
        }

        if (!empty($rimasti)) {
            $fasiSenzaMatch = $fasi->reject(fn($f) => isset($result[$f->id]));
            $target = $fasiSenzaMatch->isNotEmpty() ? $fasiSenzaMatch->first() : $fasi->first();
            $result[$target->id] = array_merge($result[$target->id] ?? [], $rimasti);
        }

        return $result;
    }

    /**
     * Estrae il prefisso dal nome workstep Prinect.
     * "interno_FB 001 4/0" → "interno", "copertina_FB 001 0/4" → "copertina"
     */
    protected function estraiPrefissoWorkstep(?string $name): string
    {
        if (!$name) return '';
        if (preg_match('/^(.+?)_FB\b/i', $name, $m)) {
            return strtolower(trim($m[1]));
        }
        return '';
    }

    /**
     * Auto-termina fasi stampa offset usando lo stato workstep Prinect (COMPLETED).
     * Per ogni commessa con fasi non terminate ma con produzione, chiede all'API Prinect
     * se i workstep di stampa sono COMPLETED.
     */
    protected function controllaCompletamentoPrinect(): void
    {
        // Include anche fasi con fogli_buoni=0 (il workstep potrebbe avere dati che le attività non hanno)
        // Include stato 3 (terminate) per permettere aggiornamento dati Prinect arrivati tardi.
        // NO stato 4 (consegnate) per evitare modifiche a dati ormai storicizzati.
        $fasiNonTerminate = OrdineFase::with('ordine')
            ->whereIn('stato', [0, 1, 2, 3])
            ->where(function ($q) {
                $q->where('fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('fase', 'STAMPA')
                  ->orWhere('fase', 'LIKE', 'STAMPA XL%');
            })
            ->get()
            ->groupBy(fn($f) => $f->ordine->commessa ?? '');

        foreach ($fasiNonTerminate as $commessa => $fasi) {
            $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
            if (!$jobId || !is_numeric($jobId)) continue;

            try {
                $wsData = $this->prinect->getJobWorksteps($jobId);
                $worksteps = collect($wsData['worksteps'] ?? [])
                    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

                if ($worksteps->isEmpty()) continue;

                // Protezione: verifica che la stampa sia effettivamente avvenuta
                // Usa workstep activities (endpoint più affidabile) come prova certa
                $wsConStart = $worksteps->filter(fn($ws) => !empty($ws['actualStartDate']));
                $totaleBuoniCheck = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);
                $allCompletedCheck = $worksteps->every(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');

                // Se nessun actualStartDate, verifica tramite workstep activities API
                $stampaConfermata = $wsConStart->isNotEmpty();
                if (!$stampaConfermata && $allCompletedCheck && $totaleBuoniCheck > 0) {
                    // Chiedi a Prinect: il workstep ha attività reali?
                    foreach ($worksteps as $ws) {
                        try {
                            $wsAct = $this->prinect->getWorkstepActivities($jobId, $ws['id']);
                            $attivitaWs = collect($wsAct['activities'] ?? []);
                            // Se almeno un'attività ha goodCycles > 0 o è di tipo "Tempo di esecuzione"
                            $haStampato = $attivitaWs->contains(function ($a) {
                                return ($a['goodCycles'] ?? 0) > 0
                                    || str_contains($a['timeTypeName'] ?? '', 'esecuzione');
                            });
                            if ($haStampato) {
                                $stampaConfermata = true;
                                break;
                            }
                        } catch (\Exception $e) {
                            // API non disponibile, non confermare
                        }
                    }
                }

                if (!$stampaConfermata) continue;

                // Aggiorna fogli_buoni/scarto dal totale workstep (più affidabile delle singole attività)
                $totaleBuoniWs = $worksteps->sum(fn($ws) => $ws['amountProduced'] ?? 0);
                $totaleScartaWs = $worksteps->sum(fn($ws) => $ws['wasteProduced'] ?? 0);

                // Aggiorna fogli per-workstep se match 1:1, altrimenti totale su tutte
                $wsValues = $worksteps->values();
                $fasiValues = $fasi->values();

                if ($fasiValues->count() > 1 && $fasiValues->count() === $wsValues->count()) {
                    // Match 1:1: ogni fase prende i fogli del suo workstep
                    foreach ($fasiValues as $i => $fase) {
                        $ws = $wsValues[$i] ?? null;
                        if (!$ws) continue;
                        $wsBuoni = $ws['amountProduced'] ?? 0;
                        $wsScarto = $ws['wasteProduced'] ?? 0;
                        if ($wsBuoni > ($fase->fogli_buoni ?? 0)) {
                            $fase->fogli_buoni = $wsBuoni;
                            $fase->qta_prod = $wsBuoni;
                            if ($wsScarto > ($fase->fogli_scarto ?? 0)) {
                                $fase->fogli_scarto = $wsScarto;
                            }
                            $fase->save();
                        }
                    }
                } elseif ($totaleBuoniWs > 0) {
                    // Singola fase o numero diverso: totale su tutte
                    foreach ($fasi as $fase) {
                        if ($totaleBuoniWs > ($fase->fogli_buoni ?? 0)) {
                            $fase->fogli_buoni = $totaleBuoniWs;
                            $fase->qta_prod = $totaleBuoniWs;
                            if ($totaleScartaWs > ($fase->fogli_scarto ?? 0)) {
                                $fase->fogli_scarto = $totaleScartaWs;
                            }
                            $fase->save();
                        }
                    }
                }

                // === REGOLE TERMINAZIONE AUTOMATICA ===
                // Protezione A: attività recente (<1h) → NON terminare
                $ultimaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
                    ->orderByDesc('start_time')
                    ->value('start_time');
                $attivitaRecente = $ultimaAttivita && Carbon::parse($ultimaAttivita)->diffInMinutes(now()) < 60;
                if ($attivitaRecente) continue;

                if ($totaleBuoniWs <= 0) continue;

                $allCompleted = $worksteps->every(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');
                $anyWaiting = $worksteps->contains(fn($ws) => ($ws['status'] ?? '') === 'WAITING');
                $totaleFogliWs = $totaleBuoniWs + $totaleScartaWs;

                // === TERMINAZIONE PER SINGOLO WORKSTEP ===
                // Se N fasi STAMPAXL = N workstep, termina individualmente ogni fase
                // quando il suo workstep corrispondente è COMPLETED
                $wsValues = $worksteps->values();
                $fasiValues = $fasi->values();

                if ($fasiValues->count() > 1 && $fasiValues->count() === $wsValues->count()) {
                    // Corrispondenza 1:1 (ordinati per indice)
                    foreach ($fasiValues as $i => $fase) {
                        $ws = $wsValues[$i] ?? null;
                        if (!$ws || $fase->stato >= 3) continue;

                        if (($ws['status'] ?? '') === 'COMPLETED' && ($ws['amountProduced'] ?? 0) > 0) {
                            $wsBuoni = $ws['amountProduced'] ?? 0;
                            $wsScarto = $ws['wasteProduced'] ?? 0;
                            $wsEnd = $ws['actualEndDate'] ?? null;

                            $fase->stato = 3;
                            $fase->data_fine = $wsEnd
                                ? Carbon::parse($wsEnd)->format('Y-m-d H:i:s')
                                : now()->format('Y-m-d H:i:s');
                            $fase->fogli_buoni = $wsBuoni;
                            $fase->qta_prod = $wsBuoni;
                            $fase->fogli_scarto = $wsScarto;
                            $fase->save();
                            FaseStatoService::ricalcolaStati($fase->ordine_id);
                        }
                    }
                    continue; // Gestito individualmente, skip logica globale
                }

                // === TERMINAZIONE GLOBALE (commesse con 1 fase STAMPAXL) ===
                $deveTerminare = false;
                $qtaCarta = $fasi->first()->ordine->qta_carta ?? 0;

                // Regola 1: TUTTI workstep COMPLETED
                if ($allCompleted) $deveTerminare = true;

                // Regole 2 e 6: fogli >= qta_carta (solo se no WAITING e con actualEndDate)
                $wsTerminati = $worksteps->filter(fn($ws) => !empty($ws['actualStartDate']) && !empty($ws['actualEndDate']));
                if (!$anyWaiting && $wsTerminati->isNotEmpty()) {
                    if (!$deveTerminare && $qtaCarta > 0 && $totaleBuoniWs >= $qtaCarta) $deveTerminare = true;
                    if (!$deveTerminare && $qtaCarta > 0 && $totaleFogliWs >= $qtaCarta) $deveTerminare = true;
                }

                if (!$deveTerminare) continue;

                $lastEnd = $worksteps->map(fn($ws) => $ws['actualEndDate'] ?? null)
                    ->filter()
                    ->sort()
                    ->last();

                foreach ($fasi as $fase) {
                    if ($fase->stato < 3) {
                        $fase->stato = 3;
                        $fase->data_fine = $lastEnd
                            ? Carbon::parse($lastEnd)->format('Y-m-d H:i:s')
                            : now()->format('Y-m-d H:i:s');
                        $fase->save();
                    }
                }
                FaseStatoService::ricalcolaStati($fasi->first()->ordine_id);
            } catch (\Exception $e) {
                // Skip se API Prinect non disponibile
            }
        }
    }

    /**
     * Ripristina a stato 2 (avviato) le fasi stampa offset che sono state erroneamente
     * terminate (stato 3) ma hanno attività Prinect recenti (ultima ora).
     * Significa che la macchina sta ancora lavorando su quella commessa.
     */
    protected function ripristinaFasiAttive(): void
    {
        // Raggruppa fasi terminate per commessa
        $fasiTerminate = OrdineFase::with('ordine')
            ->where('fogli_buoni', '>', 0)
            ->where('stato', 3)
            ->where(function ($q) {
                $q->where('fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('fase', 'STAMPA')
                  ->orWhere('fase', 'LIKE', 'STAMPA XL%');
            })
            ->get()
            ->groupBy(fn($f) => $f->ordine->commessa ?? '');

        foreach ($fasiTerminate as $commessa => $fasi) {
            if (!$commessa) continue;
            $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
            if (!$jobId || !is_numeric($jobId)) continue;

            // Check 1: attività recente — ma deve essere per la fase specifica
            // Se nessuna attività recente, non ripristinare
            $ultimaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
                ->orderByDesc('start_time')
                ->first();
            $attivitaRecente = $ultimaAttivita && $ultimaAttivita->start_time
                && Carbon::parse($ultimaAttivita->end_time ?? $ultimaAttivita->start_time)->diffInMinutes(now()) < 60;

            if (!$attivitaRecente) continue; // nessuna attività recente → non ripristinare

            // Check 2: controlla per-workstep quale fase ripristinare
            try {
                $wsData = $this->prinect->getJobWorksteps($jobId);
                $worksteps = collect($wsData['worksteps'] ?? [])
                    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
                    ->values();

                if ($worksteps->isEmpty()) continue;

                $fasiValues = $fasi->values();

                // Match 1:1 se stesso numero
                if ($fasiValues->count() === $worksteps->count()) {
                    foreach ($fasiValues as $i => $fase) {
                        $ws = $worksteps[$i] ?? null;
                        if (!$ws) continue;
                        // Ripristina solo se il workstep specifico NON è COMPLETED
                        // Skip se terminata manualmente dall'owner
                        if (($ws['status'] ?? '') !== 'COMPLETED' && !$fase->terminata_manualmente) {
                            $fase->stato = 2;
                            $fase->data_fine = null;
                            $fase->save();
                        }
                    }
                } else {
                    // Numero diverso: ripristina solo se TUTTI i workstep non sono COMPLETED
                    $allNotCompleted = $worksteps->every(fn($ws) => ($ws['status'] ?? '') !== 'COMPLETED');
                    if ($allNotCompleted) {
                        foreach ($fasiValues as $fase) {
                            if ($fase->terminata_manualmente) continue;
                            $fase->stato = 2;
                            $fase->data_fine = null;
                            $fase->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                // API non disponibile, non ripristinare
            }
        }
    }

    /**
     * Termina fasi stampa offset "abbandonate": stato 2 (avviato) con produzione,
     * ma la cui ultima attività Prinect è > 4 ore fa E in un giorno diverso da oggi.
     * Questo cattura commesse completate ieri sera che non vengono mai auto-terminate
     * perché non appaiono nelle attività live di oggi.
     */
    protected function terminaFasiAbbandonate(): void
    {
        $fasiAvviate = OrdineFase::with('ordine')
            ->where('fogli_buoni', '>', 0)
            ->where('stato', 2)
            ->where(function ($q) {
                $q->where('fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('fase', 'STAMPA')
                  ->orWhere('fase', 'LIKE', 'STAMPA XL%');
            })
            ->get();

        foreach ($fasiAvviate as $fase) {
            $commessa = $fase->ordine->commessa ?? '';
            if (!$commessa) continue;

            $ultimaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
                ->orderByDesc('start_time')
                ->first();

            if (!$ultimaAttivita || !$ultimaAttivita->start_time) continue;

            $ultimoTempo = Carbon::parse($ultimaAttivita->end_time ?? $ultimaAttivita->start_time);
            $orePassate = $ultimoTempo->diffInHours(now());
            $giornoAttivita = $ultimoTempo->toDateString();
            $oggi = Carbon::today()->toDateString();

            // Termina solo se: > 4 ore fa E giorno diverso da oggi
            if ($orePassate >= 4 && $giornoAttivita !== $oggi) {
                $fase->stato = 3;
                $fase->data_fine = $ultimoTempo->format('Y-m-d H:i:s');
                $fase->save();
                FaseStatoService::ricalcolaStati($fase->ordine_id);
            }
        }
    }

    /**
     * Cerca operatori gestionale matchando per nome+cognome nel reparto stampa offset.
     * @param array $operatoriPrinect Array di ['nome' => '...', 'cognome' => '...']
     */
    protected function matchOperatori(array $operatoriPrinect): array
    {
        $repartoId = Reparto::where('nome', 'stampa offset')->value('id');
        $matched = [];

        foreach ($operatoriPrinect as $op) {
            $cognomeLower = strtolower(trim($op['cognome'] ?? ''));
            $nomeLower = strtolower(trim($op['nome'] ?? ''));
            if (!$cognomeLower) continue;

            // Match nome + cognome nel reparto stampa offset
            $query = Operatore::whereRaw('LOWER(cognome) = ?', [$cognomeLower])
                ->where('attivo', 1);

            if ($nomeLower) {
                $query->whereRaw('LOWER(nome) = ?', [$nomeLower]);
            }

            if ($repartoId) {
                $query->where('reparto_id', $repartoId);
            }

            $found = $query->first();

            // Fallback 1: solo cognome + reparto (senza nome)
            if (!$found && $nomeLower && $repartoId) {
                $found = Operatore::whereRaw('LOWER(cognome) = ?', [$cognomeLower])
                    ->where('attivo', 1)
                    ->where('reparto_id', $repartoId)
                    ->first();
            }

            // Fallback 2: nome + cognome senza filtro reparto
            if (!$found && $nomeLower) {
                $found = Operatore::whereRaw('LOWER(cognome) = ?', [$cognomeLower])
                    ->whereRaw('LOWER(nome) = ?', [$nomeLower])
                    ->where('attivo', 1)
                    ->first();
            }

            // Fallback 3: solo cognome senza filtro reparto
            if (!$found) {
                $found = Operatore::whereRaw('LOWER(cognome) = ?', [$cognomeLower])
                    ->where('attivo', 1)
                    ->first();
            }

            if ($found) {
                $matched[] = $found;
            }
        }

        return $matched;
    }

    /**
     * Estrae la parte numerica dal job ID Prinect.
     * Es. "66698 int" → "66698", "66410" → "66410", "abc" → null
     */
    public static function estraiJobIdNumerico($jobId): ?string
    {
        if (!$jobId) return null;
        if (is_numeric($jobId)) return (string) $jobId;
        if (preg_match('/^(\d+)/', trim($jobId), $m)) return $m[1];
        return null;
    }
}
