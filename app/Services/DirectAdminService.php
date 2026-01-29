<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DirectAdminService
{
    protected function getAuthHeader()
    {
        $auth = base64_encode(config('services.directadmin.username').':'.config('services.directadmin.password'));

        return ['Authorization' => 'Basic '.$auth];
    }

    public function getPackages(): array
    {
        $url = config('services.directadmin.url').'/CMD_API_PACKAGES_USER';

        try {
            $response = Http::withHeaders($this->getAuthHeader())
                ->timeout(30)
                ->withOptions([
                    'verify' => false,
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new \Exception('HTTP Status: '.$response->status());
            }

            // Ubah dari string JSON menjadi array PHP
            $body = $response->body();
            parse_str($body, $parsed);
            $packages = $parsed['list'] ?? [];

            return $packages;
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function getPackageDetail($packageName): array
    {
        $url = config('services.directadmin.url').'/CMD_API_PACKAGES_USER?package='.$packageName;
        $response = Http::withHeaders($this->getAuthHeader())
            ->timeout(30)
            ->get($url);

        parse_str($response->body(), $detail);

        return $detail;
    }
}
