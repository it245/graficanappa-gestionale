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
    public function __construct(
        private readonly PrinectApiInterface $api,
    ) {
    }

    /**
     * Tempi di un workstep aggregando le activity API.
     */
    public function getTempi(WorkstepId $ws): TempiStampa
    {
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
