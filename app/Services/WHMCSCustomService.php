<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class WHMCSCustomService
{

    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.whmcs.custom_url');
    }

    public function getProducts(): array
    {
        $response = Http::asForm()->get($this->apiUrl . '/get-directadmin-packages.php', [
            'responsetype' => 'json',
            'timeout' => '30',
        ]);

        return $response->json('packages') ?? [];
    }

    public function getDomainsExpiry(?string $month = null): array
    {
        $params = [
            'action'       => 'expiry',
            'responsetype' => 'json',
            'timeout'      => 30,
            'month'         => $month ?? date('Y-m'),
        ];

        $url = rtrim($this->apiUrl, '/') . '/get-domains.php';

        try {
            $response = Http::timeout(30)->get($url, $params);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                    'url'    => $url . '?' . http_build_query($params),
                ];
            }

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            return ['success' => false, 'type' => 'connection', 'message' => $e->getMessage()];
        } catch (RequestException $e) {
            // biasanya muncul kalau pakai ->throw()
            return [
                'success' => false,
                'type' => 'request',
                'status' => optional($e->response)->status(),
                'body' => optional($e->response)->body(),
                'message' => $e->getMessage(),
                'url'    => $url . '?' . http_build_query($params),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'type' => 'other', 'message' => $e->getMessage()];
        }
    }
}
