<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Adapters;

use App\Http\Services\FieryService;
use App\Modules\Stampa\Contracts\StampaIntegrationInterface;
use Throwable;

/**
 * Adapter che espone FieryService (Canon V900 / HP Indigo via Fiery API)
 * dietro l'interfaccia comune StampaIntegrationInterface.
 *
 * Non modifica il service legacy: si limita a tradurre la forma del
 * payload nel formato richiesto da StampaIntegrationInterface.
 */
final class FieryAdapter implements StampaIntegrationInterface
{
    public function __construct(
        private readonly FieryService $fiery,
    ) {
    }

    public function getId(): string
    {
        return 'fiery';
    }

    public function getJobInStampa(): ?array
    {
        try {
            $status = $this->fiery->getServerStatus(true);
            if (!is_array($status)) {
                return null;
            }

            // Fiery: server status restituisce currentJob quando attivo.
            $current = $status['currentJob'] ?? $status['active_job'] ?? null;
            if (!is_array($current)) {
                return null;
            }

            return [
                'jobId'          => $current['id'] ?? $current['jobId'] ?? null,
                'nome'           => $current['name'] ?? $current['title'] ?? '',
                'copieFatte'     => (int) ($current['printedCopies']  ?? $current['copies_done'] ?? 0),
                'copieRichieste' => (int) ($current['totalCopies']    ?? $current['copies']      ?? 0),
                'inizio'         => $current['startTime'] ?? null,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getJobsCompletati(?\DateTimeInterface $da = null, ?\DateTimeInterface $a = null): array
    {
        $da ??= new \DateTimeImmutable('-1 day');
        $a  ??= new \DateTimeImmutable('now');

        try {
            // FieryService espone diversi metodi di accounting; usiamo quello
            // pubblico più stabile se presente, altrimenti restituiamo array vuoto.
            if (!method_exists($this->fiery, 'getAccountingJobs')) {
                return [];
            }

            $jobs = $this->fiery->getAccountingJobs(
                $da->format('Y-m-d'),
                $a->format('Y-m-d')
            );
            if (!is_array($jobs)) {
                return [];
            }

            $out = [];
            foreach ($jobs as $j) {
                $out[] = [
                    'jobId'          => $j['id'] ?? $j['jobId'] ?? null,
                    'nome'           => $j['name'] ?? $j['title'] ?? '',
                    'copieFatte'     => (int) ($j['printedCopies'] ?? $j['copies'] ?? 0),
                    'copieRichieste' => (int) ($j['plannedCopies'] ?? $j['copies'] ?? 0),
                    'fogliScarto'    => (int) ($j['wastedCopies']  ?? 0),
                    'inizio'         => $j['startTime'] ?? null,
                    'fine'           => $j['endTime']   ?? null,
                ];
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function isOnline(): bool
    {
        try {
            $status = $this->fiery->getServerStatus(true);
            return is_array($status) && !empty($status);
        } catch (Throwable $e) {
            return false;
        }
    }
}
