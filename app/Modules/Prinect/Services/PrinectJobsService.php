<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Services;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use App\Modules\Prinect\Enums\StatoWorkstep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Business logic sui jobs/worksteps Prinect.
 *
 * Concentra le query "intelligenti" che il legacy PrinectController e
 * PrinectSyncService duplicavano in linea: filtraggio worksteps di
 * stampa convenzionale, mapping job_id Prinect -> commessa gestionale,
 * estrazione job numerico da stringhe libere ("66698 int" -> "66698").
 *
 * Dipende solo da PrinectApiInterface: nessuna dipendenza Eloquent qui,
 * così che il service sia testabile con un fake API in pochi LOC.
 */
final class PrinectJobsService
{
    public function __construct(
        private readonly PrinectApiInterface $api,
    ) {
    }

    /**
     * Restituisce solo i workstep di tipo ConventionalPrinting per un job.
     * Filtra fuori i Plate, Cutting, ecc. che vivono nello stesso array.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function getWorkstepsStampa(string $jobId): Collection
    {
        $data = $this->api->getWorksteps($jobId);
        if (!is_array($data)) {
            return collect();
        }

        return collect($data['worksteps'] ?? [])
            ->filter(fn(array $ws) => in_array('ConventionalPrinting', $ws['types'] ?? [], true))
            ->values();
    }

    /**
     * Worksteps in un determinato stato (RUNNING/COMPLETED/ecc).
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function getWorkstepsByStato(string $jobId, StatoWorkstep $stato): Collection
    {
        return $this->getWorkstepsStampa($jobId)
            ->filter(fn(array $ws) => StatoWorkstep::tryFromApi($ws['status'] ?? null) === $stato)
            ->values();
    }

    /**
     * Mappa un commessa gestionale (formato "0066811-26") al job ID
     * numerico Prinect ("66811").
     */
    public function commessaToJobId(string $commessa): ?string
    {
        $primaParte = explode('-', $commessa)[0] ?? '';
        $numerico   = ltrim($primaParte, '0');

        return ($numerico !== '' && is_numeric($numerico)) ? $numerico : null;
    }

    /**
     * Costruisce la commessa gestionale a partire da job ID Prinect + anno.
     * Formato: "{jobId zero-padded a 7}-{anno YY}".
     */
    public function jobIdToCommessa(string $jobId, string $annoYY): string
    {
        return str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . $annoYY;
    }

    /**
     * Estrae la parte numerica iniziale da un job ID Prinect "sporco".
     * "66698 int" -> "66698", "66410" -> "66410", "abc" -> null.
     *
     * Replica l'helper statico usato da PrinectSyncService::estraiJobIdNumerico
     * — stessa semantica per evitare drift di comportamento.
     */
    public static function estraiJobIdNumerico(int|string|null $jobId): ?string
    {
        if ($jobId === null || $jobId === '') {
            return null;
        }
        if (is_numeric($jobId)) {
            return (string) $jobId;
        }
        if (preg_match('/^(\d+)/', trim((string) $jobId), $m) === 1) {
            return $m[1];
        }
        return null;
    }

    /**
     * Verifica se un workstep ha effettivamente stampato (prova certa).
     *
     * Heidelberg può marcare un workstep COMPLETED senza actualStartDate
     * (vedi bug 66811). In quel caso fallback su workstep activities:
     * se almeno un'attività ha goodCycles > 0 oppure è di tipo "esecuzione",
     * la stampa è confermata.
     */
    public function stampaConfermata(string $jobId, array $workstep): bool
    {
        if (!empty($workstep['actualStartDate'])) {
            return true;
        }

        $stato       = StatoWorkstep::tryFromApi($workstep['status'] ?? null);
        $prodotto    = (int) ($workstep['amountProduced'] ?? 0);
        $workstepId  = $workstep['id'] ?? null;

        if ($stato !== StatoWorkstep::Completed || $prodotto <= 0 || !$workstepId) {
            return false;
        }

        try {
            $resp = $this->api->getWorkstepActivities($jobId, (string) $workstepId);
            $atts = collect($resp['activities'] ?? []);

            return $atts->contains(fn(array $a) => ($a['goodCycles'] ?? 0) > 0
                || str_contains((string) ($a['timeTypeName'] ?? ''), 'esecuzione')
            );
        } catch (\Throwable $e) {
            Log::warning('Prinect sync: stampaConfermata fallback API error', [
                'jobId'       => $jobId,
                'workstepId'  => $workstepId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }
}
