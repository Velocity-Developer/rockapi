<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WHMCSService
{
    private $apiUrl;

    private $identifier;

    private $secret;

    private $accessKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whmcs.api_url');
        $this->identifier = config('services.whmcs.identifier');
        $this->secret = config('services.whmcs.secret');
        $this->accessKey = config('services.whmcs.access_key');
    }

    /**
     * Get active clients from WHMCS
     */
    public function getActiveClients(): array
    {
        try {
            $response = Http::asForm()->post($this->apiUrl, [
                'action' => 'GetClients',
                'username' => $this->identifier,
                'password' => $this->secret,
                'accesskey' => $this->accessKey,
                'responsetype' => 'json',
                'status' => 'Active',
                'limitnum' => 999999, // Get all active clients
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['result'] === 'success') {
                    return $data['clients']['client'] ?? [];
                }

                Log::error('WHMCS API Error: '.($data['message'] ?? 'Unknown error'));

                return [];
            }

            Log::error('WHMCS API Request Failed: '.$response->status());

            return [];
        } catch (\Exception $e) {
            Log::error('WHMCS Service Exception: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get client details by ID
     */
    public function getClientDetails(int $clientId): ?array
    {
        try {
            $response = Http::asForm()->post($this->apiUrl, [
                'action' => 'GetClientsDetails',
                'username' => $this->identifier,
                'password' => $this->secret,
                'accesskey' => $this->accessKey,
                'responsetype' => 'json',
                'clientid' => $clientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['result'] === 'success') {
                    return $data;
                }

                Log::error('WHMCS API Error: '.($data['message'] ?? 'Unknown error'));

                return null;
            }

            Log::error('WHMCS API Request Failed: '.$response->status());

            return null;
        } catch (\Exception $e) {
            Log::error('WHMCS Service Exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Get client products/services
     */
    public function getClientServices(int $clientId): array
    {
        try {
            $response = Http::asForm()->post($this->apiUrl, [
                'action' => 'GetClientsProducts',
                'username' => $this->identifier,
                'password' => $this->secret,
                'accesskey' => $this->accessKey,
                'responsetype' => 'json',
                'clientid' => $clientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['result'] === 'success') {
                    return $data['products']['product'] ?? [];
                }

                Log::error('WHMCS API Error: '.($data['message'] ?? 'Unknown error'));

                return [];
            }

            Log::error('WHMCS API Request Failed: '.$response->status());

            return [];
        } catch (\Exception $e) {
            Log::error('WHMCS Service Exception: '.$e->getMessage());

            return [];
        }
    }
}
