<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Services;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\ValueObjects\TempiStampa;
use App\Modules\Prinect\ValueObjects\WorkstepId;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Tempi macchina (avviamento + esecuzione) per workstep Prinect.
 *
 * Aggrega le activity di un workstep distinguendo tra "Avviamento"
 * (tipologia attività documentata da Heidelberg) e tutto il resto
 * (= esecuzione tiratura).
 *
 * Allineato 1:1 alla logica preesistente di PrinectSyncService::aggiornaFaseConAttivita
 * che è critica per la dashboard "Report Ore" → niente cambiamenti di
 * comportamento, solo estrazione in modulo testabile.
 */
final class PrinectAccountingService
{
    /**
     * Cache risposta getWorksteps per ridurre chiamate ripetute sullo
     * stesso jobId quando piu fasi appartengono allo stesso job Prinect.
     *
     * @var array<string, array<string,mixed>|null>
     */
    private array $worksetCache = [];

    public function __construct(
        private readonly PrinectApiInterface $api,
    ) {
    }

    /**
     * Tempi aggregati Heidelberg lookup per nome workstep (es. "FB 001 3/-").
     * Usato dal sync legacy (PrinectSyncService) dove sono disponibili gli
     * activity raw ma non il workstepId, solo il workstep_name.
     */
    public function getTempiByWorkstepName(string $jobId, string $workstepName): ?TempiStampa
    {
        $worksteps = $this->fetchWorksetsCached($jobId);
        if (!is_array($worksteps) || empty($worksteps['worksteps'])) {
            return null;
        }
        foreach ($worksteps['worksteps'] as $w) {
            if (($w['name'] ?? null) !== $workstepName) {
                continue;
            }
            return $this->actualTimesToTempi($w['actualTimes'] ?? []);
        }
        return null;
    }

    private function fetchWorksetsCached(string $jobId): ?array
    {
        if (array_key_exists($jobId, $this->worksetCache)) {
            return $this->worksetCache[$jobId];
        }
        try {
            $this->worksetCache[$jobId] = $this->api->getWorksteps($jobId);
        } catch (\Throwable $e) {
            Log::warning('Prinect sync: getWorksteps errore', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
            ]);
            $this->worksetCache[$jobId] = null;
        }
        return $this->worksetCache[$jobId];
    }

    /**
     * @param  array<int,array<string,mixed>>  $actualTimes
     */
    private function actualTimesToTempi(array $actualTimes): ?TempiStampa
    {
        if ($actualTimes === []) {
            return null;
        }
        $secAvv  = 0;
        $secProd = 0;
        foreach ($actualTimes as $t) {
            $name     = $t['timeTypeName'] ?? '';
            $duration = (int) ($t['duration'] ?? 0);
            if ($duration <= 0) {
                continue;
            }
            $nameLc = mb_strtolower($name);
            if (str_contains($nameLc, 'avviamento')) {
                $secAvv += $duration;
            } elseif (str_contains($nameLc, 'esecuzione') || str_contains($nameLc, 'produzione')) {
                $secProd += $duration;
            }
        }
        if ($secAvv === 0 && $secProd === 0) {
            return null;
        }
        return new TempiStampa($secAvv, $secProd);
    }

    /**
     * Tempi di un workstep.
     *
     * Strategia:
     *  1) Prima fonte (CORRETTA): legge `actualTimes` dal workstep stesso —
     *     contiene i tempi macchina aggregati ufficiali di Heidelberg
     *     (es. "Tempo di avviamento" 544s + "Tempo di esecuzione" 4220s).
     *  2) Fallback: aggrega le activity raw (somma start/end). Le activity
     *     sono pero' eventi puntuali (durata 9-21 secondi totali per job
     *     da 1h) e quindi sottostimano fortemente i tempi reali.
     *     Mantenute come ultima risorsa quando actualTimes manca.
     */
    public function getTempi(WorkstepId $ws): TempiStampa
    {
        // 1) Prova actualTimes dal workstep (fonte aggregata Heidelberg).
        try {
            $worksteps = $this->api->getWorksteps($ws->jobId);
        } catch (\Throwable $e) {
            Log::warning('Prinect sync: getWorksteps errore', [
                'jobId' => $ws->jobId,
                'wsId'  => $ws->workstepId,
                'error' => $e->getMessage(),
            ]);
            $worksteps = null;
        }

        $tempi = $this->estraiActualTimes($worksteps, $ws->workstepId);
        if ($tempi !== null) {
            return $tempi;
        }

        // 2) Fallback su activity raw (sottostima).
        try {
            $data = $this->api->getWorkstepActivities($ws->jobId, $ws->workstepId);
        } catch (\Throwable $e) {
            Log::warning('Prinect sync: getWorkstepActivities errore', [
                'jobId'  => $ws->jobId,
                'wsId'   => $ws->workstepId,
                'error'  => $e->getMessage(),
            ]);
            return TempiStampa::zero();
        }

        if (!is_array($data)) {
            return TempiStampa::zero();
        }

        return $this->aggregaAttivita($data['activities'] ?? []);
    }

    /**
     * Estrae avviamento + esecuzione da workstep.actualTimes.
     * Ritorna null se workstep non trovato o actualTimes vuoto.
     *
     * @param  array<string,mixed>|null  $worksteps  Risposta API getWorksteps
     */
    private function estraiActualTimes(?array $worksteps, string $workstepId): ?TempiStampa
    {
        if (!is_array($worksteps) || empty($worksteps['worksteps'])) {
            return null;
        }

        foreach ($worksteps['worksteps'] as $w) {
            if (($w['id'] ?? null) !== $workstepId) {
                continue;
            }

            $actualTimes = $w['actualTimes'] ?? [];
            if (!is_array($actualTimes) || $actualTimes === []) {
                return null;
            }

            $secAvv  = 0;
            $secProd = 0;
            foreach ($actualTimes as $t) {
                $name     = $t['timeTypeName'] ?? '';
                $duration = (int) ($t['duration'] ?? 0);
                if ($duration <= 0) {
                    continue;
                }
                // Heidelberg italiano: "Tempo di avviamento" / "Tempo di esecuzione".
                // Match case-insensitive su keyword per tolleranza varianti locale.
                $nameLc = mb_strtolower($name);
                if (str_contains($nameLc, 'avviamento')) {
                    $secAvv += $duration;
                } elseif (str_contains($nameLc, 'esecuzione') || str_contains($nameLc, 'produzione')) {
                    $secProd += $duration;
                }
            }

            if ($secAvv === 0 && $secProd === 0) {
                return null;
            }

            return new TempiStampa($secAvv, $secProd);
        }

        return null;
    }

    /**
     * Aggrega un array di attività API in un TempiStampa.
     *
     * Esposto pubblicamente perché PrinectSyncService legacy passa già
     * le activity arrays come collezione di Eloquent (tabella
     * prinect_attivita), evitando un round-trip API inutile.
     *
     * Convenzione "Avviamento": activity_name esatto "Avviamento" — stessa
     * stringa magica del legacy, mantenuta per non rompere i totali storici.
     *
     * @param  iterable<array<string,mixed>|object>  $activities
     */
    public function aggregaAttivita(iterable $activities): TempiStampa
    {
        $secAvv  = 0;
        $secProd = 0;

        foreach ($activities as $a) {
            // Supporta sia array (raw API) sia oggetti Eloquent (PrinectAttivita)
            $name      = is_array($a) ? ($a['name'] ?? null)     : ($a->activity_name ?? null);
            $startTime = is_array($a) ? ($a['startTime'] ?? null) : ($a->start_time ?? null);
            $endTime   = is_array($a) ? ($a['endTime'] ?? null)   : ($a->end_time ?? null);

            if (!$startTime || !$endTime) {
                continue;
            }

            $diff = Carbon::parse($startTime)->diffInSeconds(Carbon::parse($endTime));

            if ($name === 'Avviamento') {
                $secAvv += (int) $diff;
            } else {
                $secProd += (int) $diff;
            }
        }

        return new TempiStampa($secAvv, $secProd);
    }
}
