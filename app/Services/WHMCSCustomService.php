<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WHMCSCustomService
{

    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.whmcs.api_url');
    }

    public function getProducts(): array
    {
        $response = Http::asForm()->get($this->apiUrl . '/get-directadmin-packages.php', [
            'responsetype' => 'json',
            'timeout' => '30',
        ]);

        return $response->json('packages') ?? [];
    }

    public function getDomainsExpiry($date = null): array
    {
        $response = Http::timeout(30)->get($this->apiUrl . '/get-domains.php', [
            'responsetype' => 'json',
            'action' => 'expiry',
            'date'   => $date ?? date('Y-m-d'),
        ]);

        return $response->json('data') ?? [];
    }
}
