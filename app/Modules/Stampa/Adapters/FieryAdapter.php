<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Adapters;

use App\Http\Services\FieryService;
use App\Modules\Stampa\Contracts\StampaIntegrationInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Adapter che espone FieryService (Canon V900 / HP Indigo via Fiery API)
 * dietro l'interfaccia comune StampaIntegrationInterface.
 *
 * Strangler Fig: i Controller HTTP NON parlano più direttamente con
 * FieryService — passano da qui. I metodi `fiery*()` espongono
 * funzionalità Fiery-specifiche (server status, accounting per commessa,
 * client login API v5) che non hanno controparte nel mondo Prinect.
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

            // Stampa Fiery: status['stampa'] popolato dai cached jobs.
            $stampa = $status['stampa'] ?? null;
            if (!is_array($stampa) || empty($stampa['documento'])) {
                return null;
            }

            return [
                'jobId'          => null,
                'nome'           => (string) ($stampa['documento'] ?? ''),
                'copieFatte'     => (int) ($stampa['copie_fatte']  ?? 0),
                'copieRichieste' => (int) ($stampa['copie_totali'] ?? 0),
                'inizio'         => null,
                'utente'         => $stampa['utente'] ?? null,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getJobsCompletati(?\DateTimeInterface $da = null, ?\DateTimeInterface $a = null): array
    {
        try {
            $jobs = $this->fiery->getJobs();
            if (!is_array($jobs)) {
                return [];
            }

            $daTs = $da?->getTimestamp();
            $aTs  = $a?->getTimestamp();

            $out = [];
            foreach ($jobs as $j) {
                if (($j['state'] ?? '') !== 'completed') {
                    continue;
                }
                $ts = !empty($j['date']) ? strtotime((string) $j['date']) : null;
                if ($daTs && $ts && $ts < $daTs) continue;
                if ($aTs  && $ts && $ts > $aTs)  continue;

                $out[] = [
                    'jobId'          => $j['id'] ?? null,
                    'nome'           => $j['title'] ?? '',
                    'copieFatte'     => (int) ($j['copies_printed'] ?? 0),
                    'copieRichieste' => (int) ($j['num_copies']     ?? 0),
                    'fogliScarto'    => 0, // Fiery non distingue scarto
                    'inizio'         => $j['date'] ?? null,
                    'fine'           => $j['date'] ?? null,
                    'commessa'       => $j['commessa'] ?? null,
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
            return $this->fiery->isOnline();
        } catch (Throwable $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fiery-specific passthrough
    |--------------------------------------------------------------------------
    | Server status, accounting per commessa, jobs lista normalizzata —
    | concetti Fiery che non hanno equivalente Prinect (worksteps/devices).
    */

    public function fieryServerStatus(bool $fast = true): ?array
    {
        return $this->fiery->getServerStatus($fast);
    }

    public function fieryJobs(bool $fast = true): ?array
    {
        return $this->fiery->getJobs($fast);
    }

    public function fieryAccountingPerCommessa(bool $fast = true): ?array
    {
        return $this->fiery->getAccountingPerCommessa($fast);
    }

    public function fieryEstraiCommessaDaTitolo(string $title): ?string
    {
        return $this->fiery->estraiCommessaDaTitolo($title);
    }

    /**
     * Wrapper esplicito per accounting raw via login v5 → utile a chi
     * deve filtrare per data prima di aggregare. Sostituisce le
     * Http::withoutVerifying() inline che vivevano nel controller.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fieryFetchAccountingRaw(): array
    {
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;

        try {
            $loginR = Http::withoutVerifying()->timeout(15)
                ->post($baseUrl . '/live/api/v5/login', [
                    'username'     => config('fiery.username'),
                    'password'     => config('fiery.password'),
                    'accessrights' => config('fiery.api_key'),
                ]);

            if (!$loginR->successful()) return [];

            $cookies = [];
            foreach ($loginR->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            $r = Http::withoutVerifying()
                ->timeout(60)
                ->withCookies($cookies, $host)
                ->get($baseUrl . '/live/api/v5/accounting');

            // Logout (best-effort)
            try {
                Http::withoutVerifying()
                    ->withCookies($cookies, $host)
                    ->post($baseUrl . '/live/api/v5/logout');
            } catch (Throwable $e) {
                // ignored
            }

            if (!$r->successful()) return [];

            $json = $r->json();
            $items = $json['data']['items'] ?? $json;
            return is_array($items) ? $items : [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
