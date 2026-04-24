<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Helpers\DescrizioneParser;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Scheduler Mossa 37 — Simulazione a eventi discreti con propagazione
 *
 * Replica la logica di scheduler_v5.py in PHP.
 * Produce un piano di produzione ottimizzato per ogni macchina.
 */
class SchedulerService
{
    protected array $macchine;
    protected array $seqMap;
    protected array $parametri;
    protected array $faseMac; // fase_nome → macchina_id
    protected Carbon $now;

    protected float $setupPieno;
    protected float $setupRidotto;
    protected float $sogliaGg;

    public function __construct()
    {
        $cfg = config('macchine_scheduler');
        $this->macchine = $cfg['macchine'];
        $this->parametri = $cfg['parametri'];
        $this->seqMap = config('sequenza_fasi');
        $this->now = now();

        $this->setupPieno = ($cfg['setup_pieno_min'] ?? 25) / 60;
        $this->setupRidotto = ($cfg['setup_ridotto_min'] ?? 10) / 60;
        $this->sogliaGg = $cfg['soglia_batch_giorni'] ?? 5;

        // Costruisci mappa fase → macchina
        $this->faseMac = [];
        foreach ($this->macchine as $mid => $mc) {
            foreach ($mc['fasi'] as $f) {
                $this->faseMac[$f] = $mid;
            }
        }
    }

    /**
     * Esegui lo scheduler completo e salva risultati nel DB
     */
    public function esegui(): array
    {
        // 1. Carica fasi attive dal DB
        $fasi = $this->caricaFasi();
        if (empty($fasi)) return ['fasi' => 0, 'schedulate' => 0];

        // 2. Propagazione iniziale
        $this->propagaSblocchi($fasi);

        // 3. Simulazione
        $schedule = $this->simula($fasi);

        // 3.5 Calcola date BRT/spedizione: fine = max(fine predecessori nella commessa)
        $this->calcolaDateSpedizione($fasi, $schedule);

        // 4. Salva nel DB
        $this->salvaRisultati($fasi, $schedule);

        $totSched = array_sum(array_map('count', $schedule));
        $totProp = count(array_filter($fasi, fn($f) => $f['stato_orig'] == 0 && $f['sched']));

        return [
            'fasi' => count($fasi),
            'schedulate' => $totSched,
            'propagate' => $totProp,
            'per_macchina' => array_map('count', $schedule),
        ];
    }

    /**
     * Carica tutte le fasi attive (stato 0, 1, 2) dal DB
     */
    protected function caricaFasi(): array
    {
        $rows = OrdineFase::with('ordine')
            ->whereIn('stato', [0, 1, 2])
            ->whereNull('deleted_at')
            ->get();

        $fasi = [];
        $commFasi = []; // commessa → [fasi]

        foreach ($rows as $row) {
            $ordine = $row->ordine;
            if (!$ordine) continue;

            $faseNome = trim($row->fase ?? '');
            if (empty($faseNome)) continue;

            $stato = $this->parseStato($row->stato);
            if ($stato >= 3) continue;

            $mac = $this->faseMac[$faseNome] ?? null;
            $seq = $this->seqMap[$faseNome] ?? 999;
            $comm = $ordine->commessa;

            // Usa qta_fase specifica se disponibile, altrimenti fallback a qta_carta/qta_richiesta
            $qtaLavoro = $row->qta_fase ?: ($ordine->qta_carta ?: ($ordine->qta_richiesta ?: 0));
            $ore = $this->oreLavorazione($qtaLavoro, $faseNome);

            $consegna = $ordine->data_prevista_consegna
                ? Carbon::parse($ordine->data_prevista_consegna)
                : $this->now->copy()->addDays(30);
            $gg = round($consegna->diffInDays($this->now, false), 2);

            // Estrai fustella dal campo della fase o dall'ordine
            $fs = $this->estraiFustella($row, $ordine);
            $formatoCarta = $this->estraiFormato($ordine->carta ?? '');
            $colori = DescrizioneParser::parseColori($ordine->descrizione ?? '', $ordine->cliente_nome ?? '');
            $tipoOffset = $mac === 'XL106' ? $this->classificaOffset($colori) : null;

            $fid = $row->id;
            $fasi[$fid] = [
                'id' => $fid,
                'db_id' => $row->id,
                'commessa' => $comm,
                'cod_art' => $ordine->cod_art ?? '',
                'cod_carta' => trim($ordine->cod_carta ?? ''),
                'cliente' => $ordine->cliente_nome ?? '',
                'desc' => $ordine->descrizione ?? '',
                'fase' => $faseNome,
                'seq' => $seq,
                'stato_orig' => $stato,
                'consegna' => $consegna,
                'gg' => $gg,
                'qta_carta' => $qtaLavoro,
                'ore' => $ore,
                'fs' => $fs,
                'formato_carta' => $formatoCarta,
                'tipo_offset' => $tipoOffset,
                'mac' => $mac,
                'esterno' => (bool) ($row->esterno ?? false),
                'priorita_db' => $row->priorita ?? 999,
                'priorita_manuale' => (bool) ($row->priorita_manuale ?? false),
                'disponibile' => false,
                'disponibile_da' => $this->now->copy(), // sarà aggiornato da propagaSblocchi
                'in_corso' => $stato == 2,
                'completata' => false,
                'sched' => null,
                'predecessori' => [],
                'successori' => [],
            ];

            $commFasi[$comm][] = $fid;
        }

        // Costruisci dipendenze: predecessori e successori per commessa
        foreach ($commFasi as $comm => $fids) {
            foreach ($fids as $fid) {
                $fSeq = $fasi[$fid]['seq'];
                foreach ($fids as $otherId) {
                    if ($otherId === $fid) continue;
                    $oSeq = $fasi[$otherId]['seq'];
                    if ($oSeq < $fSeq) {
                        $fasi[$fid]['predecessori'][] = $otherId;
                    } elseif ($oSeq > $fSeq && $oSeq < 999) {
                        $fasi[$fid]['successori'][] = $otherId;
                    }
                }
            }
        }

        return $fasi;
    }

    /**
     * Propagazione: sblocca fasi i cui predecessori sono tutti completati/in corso
     */
    protected function propagaSblocchi(array &$fasi): void
    {
        $cambiato = true;
        while ($cambiato) {
            $cambiato = false;
            foreach ($fasi as &$f) {
                if ($f['disponibile'] || $f['completata'] || $f['in_corso']) continue;

                $pred = $f['predecessori'];

                if (empty($pred)) {
                    $f['disponibile'] = true;
                    $f['disponibile_da'] = $this->now->copy();
                    $cambiato = true;
                    continue;
                }

                // Eccezione: stampa offset/digitale (primo anello) stato 0
                if ($f['stato_orig'] == 0 && in_array($f['mac'], ['XL106', 'INDIGO'])) {
                    $predReali = array_filter($pred, function ($pid) use ($fasi) {
                        return isset($fasi[$pid])
                            && !$fasi[$pid]['completata']
                            && !$fasi[$pid]['in_corso'];
                    });
                    if (empty($predReali)) {
                        $f['disponibile'] = true;
                        $f['disponibile_da'] = $this->now->copy();
                        $cambiato = true;
                        continue;
                    }
                }

                // Tutti i predecessori completati o in corso?
                $tuttiOk = true;
                foreach ($pred as $pid) {
                    if (!isset($fasi[$pid])) continue;
                    if (!$fasi[$pid]['completata'] && !$fasi[$pid]['in_corso']) {
                        $tuttiOk = false;
                        break;
                    }
                }
                if ($tuttiOk) {
                    $f['disponibile'] = true;
                    // disponibile_da = max fine predecessori (quando sarà fisicamente pronta)
                    $maxFine = $this->now->copy();
                    foreach ($pred as $pid) {
                        if (isset($fasi[$pid]) && $fasi[$pid]['sched'] && isset($fasi[$pid]['sched']['fine'])) {
                            $finePred = $fasi[$pid]['sched']['fine'];
                            if ($finePred > $maxFine) $maxFine = $finePred->copy();
                        }
                    }
                    $f['disponibile_da'] = $maxFine;
                    $cambiato = true;
                }
            }
            unset($f);
        }
    }

    /**
     * Simulazione a eventi discreti
     */
    protected function simula(array &$fasi): array
    {
        $schedule = [];
        $macTempo = [];
        $macFsCorr = [];
        $macConfig = []; // per BOBST e PIEGA

        foreach ($this->macchine as $mid => $mc) {
            $schedule[$mid] = [];
            $macTempo[$mid] = $this->now->copy();
            $macFsCorr[$mid] = null;
            $macConfig[$mid] = null;
        }

        // Fasi in corso → completate a NOW
        foreach ($fasi as &$f) {
            if ($f['in_corso']) {
                $f['completata'] = true;
                $f['sched'] = [
                    'mac' => $f['mac'] ?? '?',
                    'inizio' => $this->now->copy(),
                    'fine' => $this->now->copy(),
                    'setup_h' => 0,
                    'setup_tipo' => 'IN CORSO',
                    'batch_group' => null,
                ];
            }
        }
        unset($f);

        $this->propagaSblocchi($fasi);

        $maxIter = 5000;
        for ($iter = 0; $iter < $maxIter; $iter++) {
            $qualcosaFatto = false;

            foreach ($this->macchine as $mid => $mc) {
                $turni = $mc['turni'];

                // Fasi disponibili per questa macchina
                $disponibili = array_filter($fasi, fn($f) =>
                    $f['disponibile'] && !$f['completata'] && $f['sched'] === null && $f['mac'] === $mid
                );
                if (empty($disponibili)) continue;

                // Gestione configurazioni BOBST/PIEGA
                if (isset($mc['config'])) {
                    $disponibili = $this->gestisciConfig($mid, $mc, $disponibili, $macTempo, $macConfig, $turni);
                    if (empty($disponibili)) continue;
                }

                // Ordina: priorità manuale prima di tutto, poi per urgenza (gg crescente)
                usort($disponibili, function ($a, $b) {
                    $aManuale = $a['priorita_manuale'] ?? false;
                    $bManuale = $b['priorita_manuale'] ?? false;
                    if ($aManuale && !$bManuale) return -1;
                    if (!$aManuale && $bManuale) return 1;
                    if ($aManuale && $bManuale) return ($a['priorita_db'] ?? 0) <=> ($b['priorita_db'] ?? 0);
                    return $a['gg'] <=> $b['gg'];
                });

                // Batching intelligente
                $piuUrgente = $disponibili[0];
                $batchKey = $this->getBatchKey($piuUrgente, $mid);

                // Cerca affinità con ultimo lavoro
                $fsCorr = $macFsCorr[$mid];
                if ($fsCorr) {
                    foreach ($disponibili as $f) {
                        $fk = $this->getBatchKey($f, $mid);
                        if ($fk === $fsCorr && abs($f['gg'] - $piuUrgente['gg']) <= $this->sogliaGg) {
                            $batchKey = $fk;
                            $piuUrgente = $f;
                            break;
                        }
                    }
                }

                // Espandi batch
                $batch = [$piuUrgente];
                $batchIds = [$piuUrgente['id'] => true];
                foreach ($disponibili as $f) {
                    if (isset($batchIds[$f['id']])) continue;
                    $fk = $this->getBatchKey($f, $mid);
                    if ($fk === $batchKey && abs($f['gg'] - $piuUrgente['gg']) <= $this->sogliaGg) {
                        $batch[] = $f;
                        $batchIds[$f['id']] = true;
                    }
                }
                // Ordina batch: per PIEGA/FIN raggruppa per cod_art (articoli uguali consecutivi)
                if (in_array($mid, ['PIEGA', 'FIN'])) {
                    usort($batch, function ($a, $b) {
                        $artCmp = ($a['cod_art'] ?? '') <=> ($b['cod_art'] ?? '');
                        return $artCmp !== 0 ? $artCmp : $a['gg'] <=> $b['gg'];
                    });
                } else {
                    usort($batch, fn($a, $b) => $a['gg'] <=> $b['gg']);
                }

                // Schedula il batch
                $t = $macTempo[$mid]->copy();
                foreach ($batch as $j => $scelta) {
                    $fid = $scelta['id'];

                    if ($j === 0 && empty($schedule[$mid])) {
                        $setup = $this->setupPieno; $st = 'PIENO (primo)';
                    } elseif ($j === 0 && $fsCorr && $this->getBatchKey($scelta, $mid) === $fsCorr) {
                        $setup = $this->setupRidotto; $st = "RIDOTTO ($fsCorr)";
                    } elseif ($j === 0) {
                        $setup = $this->setupPieno; $st = 'PIENO';
                    } else {
                        $setup = $this->setupRidotto; $st = "RIDOTTO (batch $batchKey)";
                    }

                    // La macchina non può iniziare prima che la fase sia disponibile
                    $tEff = $t->copy();
                    $dispDa = $fasi[$fid]['disponibile_da'] ?? $this->now;
                    if ($dispDa > $tEff) $tEff = $dispDa->copy();
                    $inizio = $this->avanzaTempo($tEff, $setup, $turni);
                    $fine = $this->avanzaTempo($inizio, $scelta['ore'], $turni);

                    $fasi[$fid]['sched'] = [
                        'mac' => $mid,
                        'inizio' => $inizio,
                        'fine' => $fine,
                        'setup_h' => $setup,
                        'setup_tipo' => $st,
                        'batch_group' => $batchKey,
                    ];
                    $fasi[$fid]['completata'] = true;
                    $fasi[$fid]['disponibile'] = false;

                    $schedule[$mid][] = $fasi[$fid];
                    $t = $fine->copy();
                }

                $macTempo[$mid] = $t;
                $macFsCorr[$mid] = $batchKey;
                $qualcosaFatto = true;

                $this->propagaSblocchi($fasi);
            }

            if (!$qualcosaFatto) break;
        }

        return $schedule;
    }

    /**
     * Gestione configurazioni BOBST / PIEGA
     */
    protected function gestisciConfig(string $mid, array $mc, array $disponibili, array &$macTempo, array &$macConfig, string $turni): array
    {
        $perCfg = [];
        foreach ($disponibili as $f) {
            $cfg = $this->getConfigFase($mid, $mc, $f);
            $perCfg[$cfg][] = $f;
        }

        $currentCfg = $macConfig[$mid];

        if ($currentCfg && isset($perCfg[$currentCfg])) {
            return $perCfg[$currentCfg];
        }

        // Scegli config con lavoro più urgente
        $bestCfg = null;
        $bestGg = PHP_FLOAT_MAX;
        foreach ($perCfg as $cfg => $fl) {
            $minGg = min(array_column($fl, 'gg'));
            if ($minGg < $bestGg) {
                $bestGg = $minGg;
                $bestCfg = $cfg;
            }
        }

        if ($currentCfg !== null && $bestCfg !== $currentCfg) {
            $cambioOre = $mc['cambio_config_ore'] ?? 1.0;
            $macTempo[$mid] = $this->avanzaTempo($macTempo[$mid], $cambioOre, $turni);
        }

        $macConfig[$mid] = $bestCfg;
        return $perCfg[$bestCfg] ?? [];
    }

    protected function getConfigFase(string $mid, array $mc, array $fase): string
    {
        foreach ($mc['config'] as $cfgName => $cfgFasi) {
            if (in_array($fase['fase'], $cfgFasi)) return $cfgName;
        }
        return 'DEFAULT';
    }

    /**
     * Batch key per raggruppamento
     */
    protected function getBatchKey(array $f, string $mid): string
    {
        return match ($mid) {
            'XL106' => ($f['tipo_offset'] ?? 'STD') . ($f['cod_carta'] ? '|' . $f['cod_carta'] : ''),
            'BOBST', 'STEL', 'JOH' => $f['fs'] ?? 'NOFS_' . $f['commessa'],
            'PLAST' => $f['formato_carta']
                ? $f['fase'] . '|' . $f['formato_carta']
                : $f['fase'],
            // PIEGA e FIN: raggruppa per fustella (stessa fustella = setup ridotto)
            // Dentro il batch, l'ordinamento per cod_art mette articoli uguali consecutivi
            'PIEGA', 'FIN' => $f['fs'] ?? 'NOFS_' . $f['commessa'],
            default => $f['fs'] ?? 'NOFS_' . $f['commessa'],
        };
    }

    /**
     * Avanza tempo rispettando turni e weekend
     */
    protected function avanzaTempo(Carbon $dt, float $ore, string $turniTipo): Carbon
    {
        $t = $dt->copy();
        $rimaste = $ore;

        for ($i = 0; $i < 5000 && $rimaste > 0.001; $i++) {
            $dow = $t->dayOfWeekIso; // 1=lun, 7=dom
            $isSab = $dow === 6;
            $isDom = $dow === 7;

            // Domenica: sempre off. Sabato: off tranne standard_sab o h24
            if ($isDom || ($isSab && !in_array($turniTipo, ['h24','standard_sab']))) {
                $t = $t->next(Carbon::MONDAY)->startOfDay();
                if ($turniTipo !== 'h24') $t->hour = 6;
                continue;
            }

            if ($turniTipo === 'h24') {
                [$inizio, $fine] = [0, 24];
            } elseif ($isSab) { // standard_sab sabato: 6-13
                [$inizio, $fine] = [6, 13];
            } else { // standard o standard_sab lun-ven: 6-22
                [$inizio, $fine] = [6, 22];
            }
            $oraCorrente = $t->hour + $t->minute / 60;

            if ($oraCorrente < $inizio) {
                $t->hour = $inizio;
                $t->minute = 0;
                $t->second = 0;
                continue;
            }
            if ($oraCorrente >= $fine) {
                $t->addDay()->startOfDay();
                continue;
            }

            $disponibili = $fine - $oraCorrente;
            $usate = min($rimaste, $disponibili);
            $rimaste -= $usate;
            $t->addMinutes((int) round($usate * 60));
        }

        return $t;
    }

    /**
     * Salva risultati nel DB
     */
    protected function salvaRisultati(array $fasi, array $schedule): void
    {
        // Reset tutti i campi scheduler
        DB::table('ordine_fasi')
            ->whereNull('deleted_at')
            ->update([
                'sched_posizione' => null,
                'sched_macchina' => null,
                'sched_inizio' => null,
                'sched_fine' => null,
                'sched_setup_h' => null,
                'sched_setup_tipo' => null,
                'sched_batch_group' => null,
                'sched_calcolato_at' => null,
            ]);

        $adesso = now();

        // Aggiorna campi base per tutte le fasi caricate
        foreach ($fasi as $f) {
            $seq = $this->seqMap[$f['fase']] ?? 999;
            $consegna = $f['consegna'];
            $giorniRimasti = $f['gg'];
            $giorniLavResiduo = $f['ore'] / 16; // stima: 16h/giorno lavorativo
            $urgenzaReale = $giorniRimasti - $giorniLavResiduo;

            if ($urgenzaReale < 0) $fascia = 0;
            elseif ($urgenzaReale <= 5) $fascia = 1;
            elseif ($urgenzaReale <= 15) $fascia = 2;
            else $fascia = 3;

            $batchKey = $f['mac'] ? $this->getBatchKey($f, $f['mac']) : null;
            $prioritaM37 = ($fascia * 10000) + ($urgenzaReale * 100) + $seq;

            DB::table('ordine_fasi')->where('id', $f['db_id'])->update([
                'disponibile_m37' => $f['disponibile'] || $f['completata'],
                'urgenza_reale' => round($urgenzaReale, 2),
                'fascia_urgenza' => $fascia,
                'giorni_lavoro_residuo' => round($giorniLavResiduo, 2),
                'batch_key' => $batchKey ? substr($batchKey, 0, 50) : null,
                'sequenza_m37' => $seq,
                'priorita_m37' => round($prioritaM37, 2),
            ]);
        }

        // Salva risultati schedule
        foreach ($schedule as $mid => $fasiMac) {
            foreach ($fasiMac as $pos => $f) {
                if (!$f['sched'] || $f['sched']['setup_tipo'] === 'IN CORSO') continue;

                DB::table('ordine_fasi')->where('id', $f['db_id'])->update([
                    'sched_posizione' => $pos + 1,
                    'sched_macchina' => $mid,
                    'sched_inizio' => $f['sched']['inizio']->format('Y-m-d H:i:s'),
                    'sched_fine' => $f['sched']['fine']->format('Y-m-d H:i:s'),
                    'sched_setup_h' => round($f['sched']['setup_h'], 4),
                    'sched_setup_tipo' => substr($f['sched']['setup_tipo'] ?? '', 0, 40),
                    'sched_batch_group' => substr($f['sched']['batch_group'] ?? '', 0, 80),
                    'sched_calcolato_at' => $adesso,
                ]);
            }
        }
    }

    // === Helper ===

    protected function oreLavorazione(float $qtaCarta, string $fase): float
    {
        $cfg = config('macchine_scheduler');
        $params = $cfg['parametri'][$fase] ?? null;
        $avv = $params[0] ?? $cfg['default_avviamento'];
        $copieOra = $params[1] ?? $cfg['default_copie_ora'];

        if ($copieOra <= 0 || $qtaCarta <= 0) return $avv;
        return $avv + ($qtaCarta / $copieOra);
    }

    protected function parseStato($stato): int
    {
        if (is_numeric($stato)) return (int) $stato;
        $lower = strtolower((string) $stato);
        if (str_contains($lower, 'fine turno') || str_contains($lower, 'fine orario')) return 2;
        if (in_array($lower, ['altro','buste in arrivo','prova','acconto','fustella non trovata'])) return 2;
        return 2; // default per stringhe anomale
    }

    protected function estraiFustella($row, $ordine): ?string
    {
        $desc = $ordine->descrizione ?? '';
        $cliente = $ordine->cliente_nome ?? '';
        $notePre = $ordine->note_prestampa ?? '';

        return DescrizioneParser::parseFustella($desc, $cliente, $notePre);
    }

    protected function estraiFormato(string $carta): string
    {
        if (empty($carta)) return '';
        if (preg_match('/(\d+[,.]?\d*)\s*[xX×]\s*(\d+[,.]?\d*)/', $carta, $m)) {
            return $m[1] . 'x' . $m[2];
        }
        return '';
    }

    protected function classificaOffset(string $colori): string
    {
        $c = strtoupper($colori);
        if (str_contains($c, 'DRIP OFF')) return 'DRIP';
        if (str_contains($c, 'PANTONE') || str_contains($c, 'PANT')) return 'PANT';
        return 'STD';
    }

    /**
     * Per ogni fase senza macchina (BRT, legatoria manuale, ecc.):
     * calcola sched_fine come il massimo sched_fine dei predecessori nella commessa.
     * Così le fasi di spedizione hanno una data prevista di completamento.
     */
    protected function calcolaDateSpedizione(array &$fasi, array &$schedule): void
    {
        // Raggruppa fasi schedulate per commessa con la loro data fine
        $maxFinePerCommessa = [];
        foreach ($fasi as &$f) {
            if ($f['sched'] && isset($f['sched']['fine'])) {
                $comm = $f['commessa'];
                $fine = $f['sched']['fine'];
                if (!isset($maxFinePerCommessa[$comm]) || $fine > $maxFinePerCommessa[$comm]) {
                    $maxFinePerCommessa[$comm] = $fine;
                }
            }
        }
        unset($f);

        // Assegna sched a fasi senza macchina (BRT, fasi manuali)
        foreach ($fasi as &$f) {
            if ($f['sched'] !== null) continue; // già schedulata
            if ($f['completata'] || $f['in_corso']) continue;

            $comm = $f['commessa'];
            if (!isset($maxFinePerCommessa[$comm])) continue;

            $fineCommessa = $maxFinePerCommessa[$comm];

            $f['sched'] = [
                'mac' => 'SPED',
                'inizio' => $fineCommessa->copy(),
                'fine' => $fineCommessa->copy(),
                'setup_h' => 0,
                'setup_tipo' => 'AUTO',
                'batch_group' => null,
            ];

            // Aggiungi alla schedule per il conteggio
            if (!isset($schedule['SPED'])) $schedule['SPED'] = [];
            $schedule['SPED'][] = $f;
        }
        unset($f);
    }
}
