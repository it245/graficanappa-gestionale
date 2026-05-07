<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Adapters;

use App\Modules\Prinect\Contracts\PrinectApiInterface;
use Illuminate\Support\Facades\Http;

/**
 * Implementazione concreta di PrinectApiInterface basata sulla Http facade
 * di Laravel.
 *
 * Mantiene 1:1 le chiamate del legacy PrinectService (app/Http/Services):
 * stessi endpoint, stessi parametri, stessi timeout, stessa autenticazione
 * Basic Auth. Sostituire questa classe NON deve cambiare il payload visto
 * dai Service consumer.
 *
 * Config env (NON cambiate dal modulo):
 *  - PRINECT_API_URL   base URL del Pressroom Manager (senza trailing slash)
 *  - PRINECT_API_USER  username Basic Auth
 *  - PRINECT_API_PASS  password Basic Auth
 */
final class PrinectHttpAdapter implements PrinectApiInterface
{
    private readonly string $baseUrl;
    private readonly string $user;
    private readonly string $pass;

    public function __construct()
    {
        $this->baseUrl = (string) env('PRINECT_API_URL', '');
        $this->user    = (string) env('PRINECT_API_USER', '');
        $this->pass    = (string) env('PRINECT_API_PASS', '');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->user !== '' && $this->pass !== '';
    }

    /**
     * Builder Http preconfigurato. Timeout default 15s come legacy.
     */
    private function api(int $timeoutSec = 15)
    {
        return Http::withBasicAuth($this->user, $this->pass)->timeout($timeoutSec);
    }

    // ===== Device =====

    public function getDevices(): ?array
    {
        $response = $this->api()->get($this->baseUrl . '/rest/device');

        if ($response->successful()) {
            return $response->json();
        }

        // Mantiene la shape "error" attesa da PrinectAdapter (Modules/Stampa)
        return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getDeviceActivity(string $deviceId, ?string $start = null, ?string $end = null): ?array
    {
        $params = [];
        if ($start !== null) $params['start'] = $start;
        if ($end !== null)   $params['end']   = $end;

        $response = $this->api(30)->get(
            "{$this->baseUrl}/rest/device/{$deviceId}/activity",
            $params
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getDeviceConsumption(string $deviceId, ?string $start = null, ?string $end = null): ?array
    {
        $params = [];
        if ($start !== null) $params['start'] = $start;
        if ($end !== null)   $params['end']   = $end;

        $response = $this->api()->get(
            "{$this->baseUrl}/rest/device/{$deviceId}/consumption",
            $params
        );

        return $response->successful() ? $response->json() : null;
    }

    // ===== Jobs =====

    public function getJobs(?string $modifiedSince = null, ?string $globalStatus = null): ?array
    {
        $params = [];
        if ($modifiedSince !== null) $params['modifiedSince'] = $modifiedSince;
        if ($globalStatus !== null)  $params['globalStatus']  = $globalStatus;

        $response = $this->api(30)->get("{$this->baseUrl}/rest/job", $params);

        return $response->successful() ? $response->json() : null;
    }

    public function getJob(string $jobId): ?array
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/job/{$jobId}");

        return $response->successful() ? $response->json() : null;
    }

    public function getWorksteps(string $jobId): ?array
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/job/{$jobId}/workstep");

        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepActivities(string $jobId, string $workstepId): ?array
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/activity"
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepInk(string $jobId, string $workstepId): ?array
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/inkConsumption"
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepQuality(string $jobId, string $workstepId): ?array
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/quality"
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepPreview(string $jobId, string $workstepId): ?array
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/preview"
        );

        return $response->successful() ? $response->json() : null;
    }

    public function getMilestones(?string $workstepId = null): ?array
    {
        // Nota: il parametro $workstepId è ignorato — l'API Heidelberg
        // espone milestones come master data globale.
        $response = $this->api()->get("{$this->baseUrl}/rest/masterdata/milestone");

        return $response->successful() ? $response->json() : null;
    }
}
