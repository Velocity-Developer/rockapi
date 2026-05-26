<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OngkirVDServices
{
    private string $apiUrl;

    private ?string $apiKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.ongkir_vd.url'), '/');
        $this->apiKey = config('services.ongkir_vd.key');
    }

    public function getShippingLogs(array $params = []): array
    {
        $url = $this->apiUrl . '/shipping-log';

        try {
            $response = Http::timeout(30)
                ->withHeaders($this->getAuthHeader())
                ->get($url, $params);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                    'url' => $url . ($params ? '?' . http_build_query($params) : ''),
                ];
            }

            return $response->json() ?? [];
        } catch (ConnectionException $e) {
            return ['success' => false, 'type' => 'connection', 'message' => $e->getMessage()];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'type' => 'request',
                'status' => optional($e->response)->status(),
                'body' => optional($e->response)->body(),
                'message' => $e->getMessage(),
                'url' => $url . ($params ? '?' . http_build_query($params) : ''),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'type' => 'other', 'message' => $e->getMessage()];
        }
    }

    private function getAuthHeader(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'key' => $this->apiKey,
        ];
    }
}
