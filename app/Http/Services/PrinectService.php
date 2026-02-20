<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class PrinectService
{
    protected $baseUrl;
    protected $user;
    protected $pass;

    public function __construct()
    {
        $this->baseUrl = env('PRINECT_API_URL');
        $this->user = env('PRINECT_API_USER');
        $this->pass = env('PRINECT_API_PASS');
    }

    private function api()
    {
        return Http::withBasicAuth($this->user, $this->pass)->timeout(15);
    }

    // ===== DEVICE API =====

    public function getDevices()
    {
        $response = $this->api()->get($this->baseUrl . '/rest/device');
        if ($response->successful()) {
            return $response->json();
        }
        return ['error' => true, 'status' => $response->status(), 'body' => $response->body()];
    }

    public function getDeviceActivity($deviceId, $start = null, $end = null)
    {
        $params = [];
        if ($start) $params['start'] = $start;
        if ($end) $params['end'] = $end;

        $response = $this->api()->timeout(30)->get(
            "{$this->baseUrl}/rest/device/{$deviceId}/activity",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getDeviceConsumption($deviceId, $start = null, $end = null)
    {
        $params = [];
        if ($start) $params['start'] = $start;
        if ($end) $params['end'] = $end;

        $response = $this->api()->get(
            "{$this->baseUrl}/rest/device/{$deviceId}/consumption",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getDeviceGroups()
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/devicegroup");
        return $response->successful() ? $response->json() : null;
    }

    // ===== JOB API =====

    public function getJobs($modifiedSince = null, $globalStatus = null)
    {
        $params = [];
        if ($modifiedSince) $params['modifiedSince'] = $modifiedSince;
        if ($globalStatus) $params['globalStatus'] = $globalStatus;

        $response = $this->api()->timeout(30)->get(
            "{$this->baseUrl}/rest/job",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getJob($jobId)
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/job/{$jobId}");
        return $response->successful() ? $response->json() : null;
    }

    public function getJobWorksteps($jobId)
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/job/{$jobId}/workstep");
        return $response->successful() ? $response->json() : null;
    }

    public function getJobElements($jobId, $query = 'ALL')
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/element",
            ['query' => $query]
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepPreview($jobId, $workstepId)
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/preview"
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepQuality($jobId, $workstepId)
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/quality"
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepInkConsumption($jobId, $workstepId)
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/inkConsumption"
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepActivity($jobId, $workstepId)
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/activity"
        );
        return $response->successful() ? $response->json() : null;
    }

    // ===== EMPLOYEE API =====

    public function getEmployees()
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/employee");
        return $response->successful() ? $response->json() : null;
    }

    public function getEmployee($employeeId)
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/employee/{$employeeId}");
        return $response->successful() ? $response->json() : null;
    }

    public function getEmployeeActivity($employeeId, $start = null, $end = null)
    {
        $params = [];
        if ($start) $params['start'] = $start;
        if ($end) $params['end'] = $end;

        $response = $this->api()->timeout(30)->get(
            "{$this->baseUrl}/rest/employee/{$employeeId}/activity",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    // ===== MASTERDATA API =====

    public function getMilestones()
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/masterdata/milestone");
        return $response->successful() ? $response->json() : null;
    }

    // ===== COMMON API =====

    public function getVersion()
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/version");
        return $response->successful() ? $response->json() : null;
    }
}
