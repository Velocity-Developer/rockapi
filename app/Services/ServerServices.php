<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Server;

class ServerServices
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public static function make(int|string $id): static
    {
        $server = Server::where('id', $id)
            ->select([
                'id',
                'name',
                'type',
                'ip_address',
                'hostname',
                'port',
                'username',
                'password',
            ])
            ->first();

        return new static($server);
    }

    public function get()
    {
        return $this->server;
    }

    public function getAuthHeader()
    {
        $auth = base64_encode($this->server->username . ':' . $this->server->password);
        return ['Authorization' => 'Basic ' . $auth];
    }

    public function getPackages(): array
    {
        $url = $this->server->hostname . '/CMD_API_PACKAGES_USER';

        try {
            $response = Http::withHeaders($this->getAuthHeader())
                ->timeout(30)
                ->withOptions([
                    'verify' => false,
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("HTTP Status: " . $response->status());
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
}
