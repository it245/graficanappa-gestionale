<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class BrtService
{
    protected $baseUrl;
    protected $userID;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = config('services.brt.base_url');
        $this->userID = config('services.brt.user_id');
        $this->password = config('services.brt.password');
    }

    public function getTracking(string $parcelID): ?array
    {
        try {
            $response = Http::withHeaders([
                'userID' => $this->userID,
                'password' => $this->password,
            ])->withOptions([
                'verify' => false,
            ])->timeout(15)->get("{$this->baseUrl}/tracking/parcelID/{$parcelID}");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => 'Errore API BRT: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Errore connessione BRT: ' . $e->getMessage(),
            ];
        }
    }
}
