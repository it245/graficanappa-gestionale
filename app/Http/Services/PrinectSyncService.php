<?php

namespace App\Http\Services;

use App\Models\PrinectAttivita;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\Reparto;
use Carbon\Carbon;

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
        $data = $this->prinect->getDeviceActivity($deviceId);

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
            $jobId = $att['workstep']['job']['id'] ?? null;
            $commessa = null;
            if ($jobId && is_numeric($jobId)) {
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
     */
    protected function aggiornaFogliCommessa(string $commessa): void
    {
        $totali = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->selectRaw('
                SUM(good_cycles) as fogli_buoni,
                SUM(waste_cycles) as fogli_scarto
            ')
            ->first();

        $tempi = PrinectAttivita::where('commessa_gestionale', $commessa)->get();
        $secAvviamento = 0;
        $secProduzione = 0;
        foreach ($tempi as $att) {
            if (!$att->start_time || !$att->end_time) continue;
            $diff = $att->start_time->diffInSeconds($att->end_time);
            if ($att->activity_name === 'Avviamento') {
                $secAvviamento += $diff;
            } else {
                $secProduzione += $diff;
            }
        }

        // Prima attivita = data_inizio
        $primaAttivita = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->whereNotNull('start_time')
            ->orderBy('start_time')
            ->first();
        $dataInizio = $primaAttivita?->start_time;

        // Estrai nome+cognome operatori Prinect unici
        $operatoriPrinect = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->whereNotNull('operatore_prinect')
            ->where('operatore_prinect', '!=', '')
            ->distinct()
            ->pluck('operatore_prinect')
            ->flatMap(function ($nomiStr) {
                // Formato: "Raffaele Barbato" o "Raffaele Barbato, Luigi Marino"
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

        // Trova fasi di stampa offset (STAMPAXL106* o STAMPA)
        $fasi = $this->troveFasiStampa($commessa);

        $this->aggiornaFasi($fasi, [
            'fogli_buoni' => $totali->fogli_buoni ?? 0,
            'fogli_scarto' => $totali->fogli_scarto ?? 0,
            'tempo_avviamento_sec' => $secAvviamento,
            'tempo_esecuzione_sec' => $secProduzione,
        ], $dataInizio, $operatoriMatched);
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

            $jobId = $att['workstep']['job']['id'] ?? null;
            if (!$jobId || !is_numeric($jobId)) continue;

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

        // Aggiorna fasi di stampa per ogni commessa trovata
        foreach ($perCommessa as $commessa => $attivitaList) {
            $this->aggiornaFaseStampaDaApi($commessa, $attivitaList);
        }
    }

    /**
     * Aggiorna fasi di stampa offset per una commessa con dati raw dalla API.
     */
    protected function aggiornaFaseStampaDaApi(string $commessa, array $attivitaApi): void
    {
        $fasi = $this->troveFasiStampa($commessa);
        if ($fasi->isEmpty()) return;

        // Calcola totali
        $buoni = 0;
        $scarto = 0;
        $secAvv = 0;
        $secProd = 0;

        foreach ($attivitaApi as $a) {
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

        // Prima attivita = data_inizio
        $sorted = collect($attivitaApi)->filter(fn($a) => isset($a['startTime']))->sortBy('startTime');
        $dataInizio = $sorted->isNotEmpty()
            ? Carbon::parse($sorted->first()['startTime'])
            : null;

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

        $this->aggiornaFasi($fasi, [
            'fogli_buoni' => $buoni,
            'fogli_scarto' => $scarto,
            'tempo_avviamento_sec' => $secAvv,
            'tempo_esecuzione_sec' => $secProd,
        ], $dataInizio, $operatoriMatched);
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
                  ->orWhere('fase', 'STAMPA');
            })
            ->get();
    }

    /**
     * Aggiorna le fasi con fogli, tempi, stato, data_inizio e operatori.
     * Stato 2 = Avviato (come da dashboard operatore)
     * Stato 3 = Terminato (quando fogli_buoni >= qta_carta)
     */
    protected function aggiornaFasi($fasi, array $dati, $dataInizio, array $operatoriMatched): void
    {
        if ($fasi->isEmpty()) return;

        $primoOperatore = $operatoriMatched[0] ?? null;

        foreach ($fasi as $fase) {
            $fase->fogli_buoni = $dati['fogli_buoni'];
            $fase->fogli_scarto = $dati['fogli_scarto'];
            $fase->tempo_avviamento_sec = $dati['tempo_avviamento_sec'];
            $fase->tempo_esecuzione_sec = $dati['tempo_esecuzione_sec'];

            // Qta carta dall'ordine
            $qtaCarta = $fase->ordine->qta_carta ?? 0;

            // Se la fase non e ancora avviata (stato 0 o 1), avviala (stato=2)
            if (in_array($fase->stato, [0, '0', 1, '1'])) {
                $fase->stato = 2;
            }

            // Se fogli_buoni >= qta_carta → fase terminata (stato=3) + data_fine
            if ($qtaCarta > 0 && $dati['fogli_buoni'] >= $qtaCarta && $fase->stato != 3 && $fase->stato !== '3') {
                $fase->stato = 3;
                if (!$fase->data_fine) {
                    $fase->data_fine = now();
                }
            }

            // Imposta data_inizio se mancante
            if (!$fase->data_inizio && $dataInizio) {
                $fase->data_inizio = $dataInizio;
            }

            // Imposta operatore_id se mancante
            if (!$fase->operatore_id && $primoOperatore) {
                $fase->operatore_id = $primoOperatore->id;
            }

            $fase->save();

            // Aggiungi tutti gli operatori alla pivot se non gia presenti
            foreach ($operatoriMatched as $op) {
                if (!$fase->operatori()->where('operatore_id', $op->id)->exists()) {
                    $fase->operatori()->attach($op->id, [
                        'data_inizio' => $dataInizio,
                    ]);
                }
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
}
