<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Adapters;

use App\Http\Services\PrinectService;
use App\Modules\Stampa\Contracts\StampaIntegrationInterface;
use Throwable;

/**
 * Adapter che espone PrinectService dietro l'interfaccia comune
 * StampaIntegrationInterface, senza modificare il service legacy.
 *
 * Pattern: Adapter (GoF) — traduce le chiamate del dominio Stampa
 * verso i metodi specifici di PrinectService (Heidelberg XL106).
 *
 * Strangler Fig: i Controller HTTP NON parlano più direttamente con
 * PrinectService ma passano da qui. I metodi `prinect*()` espongono
 * funzionalità Prinect-specifiche (worksteps, ink consumption, ecc.)
 * che non hanno controparte nel mondo Fiery — restano fuori dal
 * contratto comune ma usano comunque l'adapter come dependency point.
 */
final class PrinectAdapter implements StampaIntegrationInterface
{
    public function __construct(
        private readonly PrinectService $prinect,
    ) {
    }

    public function getId(): string
    {
        return 'prinect';
    }

    public function getJobInStampa(): ?array
    {
        if (!$this->prinect->isConfigured()) {
            return null;
        }

        try {
            // Prinect espone i workstep "InProgress": il primo è il job in stampa.
            // Riusiamo l'endpoint device activity per ottenere lo stato live.
            $devices = $this->prinect->getDevices();
            if (!is_array($devices) || isset($devices['error'])) {
                return null;
            }

            foreach ($devices as $dev) {
                $devId = $dev['id'] ?? null;
                if (!$devId) {
                    continue;
                }
                $activity = $this->prinect->getDeviceActivity((string) $devId);
                if (!is_array($activity) || empty($activity)) {
                    continue;
                }
                $live = $activity[0] ?? null;
                if (is_array($live) && (($live['status'] ?? '') === 'InProgress')) {
                    return [
                        'jobId'          => $live['jobId'] ?? null,
                        'nome'           => $live['jobName'] ?? '',
                        'copieFatte'     => (int) ($live['goodCopies']  ?? 0),
                        'copieRichieste' => (int) ($live['plannedCopies'] ?? 0),
                        'inizio'         => $live['actualStartDate'] ?? null,
                        'device'         => $devId,
                    ];
                }
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    public function getJobsCompletati(?\DateTimeInterface $da = null, ?\DateTimeInterface $a = null): array
    {
        if (!$this->prinect->isConfigured()) {
            return [];
        }

        $da ??= new \DateTimeImmutable('-1 day');
        $a  ??= new \DateTimeImmutable('now');

        $start = $da->format('Y-m-d\TH:i:s');
        $end   = $a->format('Y-m-d\TH:i:s');

        try {
            $devices = $this->prinect->getDevices();
            if (!is_array($devices) || isset($devices['error'])) {
                return [];
            }

            $out = [];
            foreach ($devices as $dev) {
                $devId = $dev['id'] ?? null;
                if (!$devId) {
                    continue;
                }
                $rows = $this->prinect->getDeviceActivity((string) $devId, $start, $end);
                if (!is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    if (($row['status'] ?? '') !== 'Completed') {
                        continue;
                    }
                    $out[] = [
                        'jobId'          => $row['jobId'] ?? null,
                        'nome'           => $row['jobName'] ?? '',
                        'copieFatte'     => (int) ($row['goodCopies']    ?? 0),
                        'copieRichieste' => (int) ($row['plannedCopies'] ?? 0),
                        'fogliScarto'    => (int) ($row['wasteCopies']   ?? 0),
                        'inizio'         => $row['actualStartDate'] ?? null,
                        'fine'           => $row['actualEndDate']   ?? null,
                        'device'         => $devId,
                    ];
                }
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function isOnline(): bool
    {
        if (!$this->prinect->isConfigured()) {
            return false;
        }

        try {
            $devices = $this->prinect->getDevices();
            return is_array($devices) && !isset($devices['error']);
        } catch (Throwable $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Prinect-specific passthrough
    |--------------------------------------------------------------------------
    | Metodi non comuni a Fiery: device telemetry XL106, worksteps con ink
    | consumption / quality / preview. Restano sull'adapter (non sul
    | contratto) per mantenere il dominio Stampa pulito.
    |
    | Sono thin wrappers su PrinectService — l'I/O HTTP resta lì, ma il
    | controller dipende dall'adapter (testabile via mock) anziché dal
    | service concreto.
    */

    public function prinectDevices(): ?array
    {
        return $this->prinect->getDevices();
    }

    public function prinectDeviceActivity(string $deviceId, ?string $start = null, ?string $end = null): ?array
    {
        return $this->prinect->getDeviceActivity($deviceId, $start, $end);
    }

    public function prinectDeviceConsumption(string $deviceId, ?string $start = null, ?string $end = null): ?array
    {
        return $this->prinect->getDeviceConsumption($deviceId, $start, $end);
    }

    public function prinectJobs($modifiedSince = null, $globalStatus = null): ?array
    {
        return $this->prinect->getJobs($modifiedSince, $globalStatus);
    }

    public function prinectJob($jobId): ?array
    {
        return $this->prinect->getJob($jobId);
    }

    public function prinectJobWorksteps($jobId): ?array
    {
        return $this->prinect->getJobWorksteps($jobId);
    }

    public function prinectJobElements($jobId, string $query = 'ALL'): ?array
    {
        return $this->prinect->getJobElements($jobId, $query);
    }

    public function prinectWorkstepInkConsumption($jobId, $workstepId): ?array
    {
        return $this->prinect->getWorkstepInkConsumption($jobId, $workstepId);
    }

    public function prinectWorkstepPreview($jobId, $workstepId): ?array
    {
        return $this->prinect->getWorkstepPreview($jobId, $workstepId);
    }

    public function prinectWorkstepQuality($jobId, $workstepId): ?array
    {
        return $this->prinect->getWorkstepQuality($jobId, $workstepId);
    }

    public function prinectMilestones(): ?array
    {
        return $this->prinect->getMilestones();
    }
}
