<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WHMCSCustomService
{

    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.whmcs.api_url') . 'get-directadmin-packages.php';
    }

    public function getProducts(): array
    {
        $response = Http::asForm()->get($this->apiUrl, [
            'responsetype' => 'json',
            'timeout' => '30',
        ]);

        return $response->json('packages') ?? [];
    }
}
