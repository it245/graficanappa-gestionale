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

    public function getJobs($modifiedSince = null)
    {
        $params = [];
        if ($modifiedSince) $params['modifiedSince'] = $modifiedSince;

        $response = $this->api()->timeout(30)->get(
            "{$this->baseUrl}/rest/job",
            $params
        );
        return $response->successful() ? $response->json() : null;
    }

    public function getJobWorksteps($jobId)
    {
        $response = $this->api()->get("{$this->baseUrl}/rest/job/{$jobId}/workstep");
        return $response->successful() ? $response->json() : null;
    }

    public function getWorkstepInkConsumption($jobId, $workstepId)
    {
        $response = $this->api()->get(
            "{$this->baseUrl}/rest/job/{$jobId}/workstep/{$workstepId}/inkConsumption"
        );
        return $response->successful() ? $response->json() : null;
    }
}
