<?php

namespace App\Http\Services;

use App\Modules\Prinect\Adapters\PrinectHttpAdapter;
use App\Modules\Prinect\Contracts\PrinectApiInterface;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper legacy del client REST Prinect Pressroom Manager.
 *
 * @deprecated Use App\Modules\Prinect\* instead.
 *
 * Storia: questa classe era il client HTTP monolitico verso Heidelberg.
 * Nel branch def2.0 è stata estratta in `App\Modules\Prinect` con
 * separazione netta tra Contract / Adapter / Services (Strangler Fig).
 *
 * Mantenuta in vita per:
 *  - backward-compat con caller esistenti (cron `prinect:sync`,
 *    KioskController, DashboardOwnerController, CommessaController);
 *  - non rompere `App\Modules\Stampa\Adapters\PrinectAdapter` che la
 *    inietta direttamente.
 *
 * Internamente delega ogni metodo a {@see PrinectHttpAdapter}, così che
 * la transport-layer viva in un unico posto. La firma resta intatta
 * per evitare refactor a cascata sui chiamanti.
 *
 * NUOVO CODICE: usare direttamente {@see PrinectApiInterface} con
 * dependency injection. Vedi i Service in
 * `App\Modules\Prinect\Services\*` per la business logic (jobs,
 * accounting, ink, auto-termina).
 */
class PrinectService
{
    /**
     * Adapter modulo Prinect: a lui deleghiamo tutte le chiamate REST.
     */
    private PrinectApiInterface $api;

    /**
     * Mantieni i tre attributi legacy per non rompere chi facesse
     * reflection/get_object_vars (paranoia, ma costa zero).
     */
    protected $baseUrl;
    protected $user;
    protected $pass;

    public function __construct(?PrinectApiInterface $api = null)
    {
        // Se il container non è ancora bootato (CLI early-bind), instanziamo
        // direttamente l'Adapter. Tutti gli env letti dentro l'Adapter.
        $this->api = $api ?? new PrinectHttpAdapter();

        $this->baseUrl = (string) env('PRINECT_API_URL', '');
        $this->user = (string) env('PRINECT_API_USER', '');
        $this->pass = (string) env('PRINECT_API_PASS', '');
    }

    public function isConfigured(): bool
    {
        return $this->api->isConfigured();
    }

    // ===== DEVICE API =====

    public function getDevices()
    {
        return $this->api->getDevices();
    }

    public function getDeviceActivity($deviceId, $start = null, $end = null)
    {
        return $this->api->getDeviceActivity((string) $deviceId, $start, $end);
    }

    public function getDeviceConsumption($deviceId, $start = null, $end = null)
    {
        return $this->api->getDeviceConsumption((string) $deviceId, $start, $end);
    }

    public function getDeviceGroups()
    {
        // Endpoint non esposto dal Contract: chiamata HTTP diretta come legacy.
        $response = $this->httpRaw()->get("{$this->baseUrl}/rest/devicegroup");
        return $response->successful() ? $response->json() : null;
    }

    /**
     * Builder Http "grezzo" per i pochi endpoint non esposti dal Contract.
     * Stessa autenticazione e timeout del legacy.
     */
    private function httpRaw(int $timeoutSec = 15)
    {
        return Http::withBasicAuth($this->user, $this->pass)->timeout($timeoutSec);
    }

    // ===== JOB API =====

    public function getJobs($modifiedSince = null, $globalStatus = null)
    {
        return $this->api->getJobs($modifiedSince, $globalStatus);
    }

    public function getJob($jobId)
    {
        return $this->api->getJob((string) $jobId);
    }

    public function getJobWorksteps($jobId)
    {
        return $this->api->getWorksteps((string) $jobId);
    }

    public function getJobElements($jobId, $query = 'ALL')
    {
        // Endpoint non esposto dal Contract (usato da PrinectController::jobDetail
        // tramite PrinectAdapter::prinectJobElements). Chiamata HTTP diretta.
        $response = $this->httpRaw()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/element",
            ['query' => $query]
        );
        return $response->successful() ? $response->json() : null;
    }

    /**
     * Attività per singolo workstep — prova certa di stampa.
     */
    public function getWorkstepActivities($jobId, $workstepId)
    {
        return $this->api->getWorkstepActivities((string) $jobId, (string) $workstepId);
    }

    public function getWorkstepPreview($jobId, $workstepId)
    {
        return $this->api->getWorkstepPreview((string) $jobId, (string) $workstepId);
    }

    public function getWorkstepQuality($jobId, $workstepId)
    {
        return $this->api->getWorkstepQuality((string) $jobId, (string) $workstepId);
    }

    public function getWorkstepInkConsumption($jobId, $workstepId)
    {
        return $this->api->getWorkstepInk((string) $jobId, (string) $workstepId);
    }

    public function getWorkstepActivity($jobId, $workstepId)
    {
        // Alias storico di getWorkstepActivities — stesso endpoint.
        return $this->api->getWorkstepActivities((string) $jobId, (string) $workstepId);
    }

    // ===== EMPLOYEE API =====

    public function getEmployees()
    {
        // Endpoint non esposto dal Contract: chiamata HTTP diretta come legacy.
        $response = $this->httpRaw()->get("{$this->baseUrl}/rest/employee");
        return $response->successful() ? $response->json() : null;
    }

    public function getEmployee($employeeId)
    {
        $response = $this->httpRaw()->get("{$this->baseUrl}/rest/employee/{$employeeId}");
        return $response->successful() ? $response->json() : null;
    }

    public function getEmployeeActivity($employeeId, $start = null, $end = null)
    {
        $params = [];
        if ($start) $params['start'] = $start;
        if ($end) $params['end'] = $end;

        $response = $this->httpRaw(30)->get(
            "{$this->baseUrl}/rest/employee/{$employeeId}/activity",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    // ===== MASTERDATA API =====

    public function getMilestones()
    {
        return $this->api->getMilestones();
    }

    // ===== COMMON API =====

    public function getVersion()
    {
        // Endpoint non esposto dal Contract: chiamata HTTP diretta come legacy.
        $response = $this->httpRaw()->get("{$this->baseUrl}/rest/version");
        return $response->successful() ? $response->json() : null;
    }
}
