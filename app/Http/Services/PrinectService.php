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
        $this->baseUrl = env('PRINECT_API_URL');   // es: http://int:15011/PrinectAPILocal
        $this->user = env('PRINECT_API_USER');
        $this->pass = env('PRINECT_API_PASS');
    }

    public function getDevices()
    {
        $response = Http::withBasicAuth($this->user, $this->pass)
            ->get($this->baseUrl . '/rest/device');

        if ($response->successful()) {
            return $response->json(); // ritorna array associativo
        }

        return [
            'error' => true,
            'status' => $response->status(),
            'body' => $response->body()
        ];
    }

    public function getDeviceActivity($deviceId)
    {
        $response = Http::withBasicAuth($this->user, $this->pass)
        ->get("{$this->baseUrl}/rest/device/{$deviceId}/activity");
        return $response->successful() ? $response->json() : null;
    }

      public function getDeviceConsumption($deviceId)
    {
        $response = Http::withBasicAuth($this->user, $this->pass)
        ->get("{$this->baseUrl}/rest/device/{$deviceId}/consumption");
        return $response->successful() ? $response->json() : null;
    }

}