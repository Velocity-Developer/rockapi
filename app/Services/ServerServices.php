<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Http;

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

        if (! $server) {
            throw new \Exception('Server not found');
        }

        $rawPassword = $server->raw_password;
        $server->raw_password = $rawPassword;

        $url = $server->hostname ? $server->hostname.':'.$server->port : '';
        if ($server->hostname) {
            // tambahkan https://
            if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                $url = 'https://'.$url;
            }
        }
        $server->url = $url;

        return new static($server);
    }

    public function get()
    {
        return $this->server;
    }

    public function getAuthHeader()
    {
        $auth = base64_encode($this->server->username.':'.$this->server->raw_password);

        return ['Authorization' => 'Basic '.$auth];
    }

    public function getPackages(): array
    {
        // jika url kosong
        if (empty($this->server->url)) {
            return ['error' => true, 'message' => 'Server URL is empty'];
        }

        $url = $this->server->url.'/CMD_API_PACKAGES_USER';

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
            return ['error' => true, 'message' => $e->getMessage(), 'url' => $url];
        }
    }

    public function getPackageDetail($packageName): array
    {
        // jika url kosong
        if (empty($this->server->url)) {
            return ['error' => true, 'message' => 'Server URL is empty'];
        }

        $url = $this->server->url.'/CMD_API_PACKAGES_USER?json=yes&package='.$packageName;

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

            // ubah dari json menjadi array php
            $body = $body ? json_decode($body, true) : [];

            return $body;
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage(), 'url' => $url];
        }
    }

    public function getUsers()
    {
        $url = $this->server->url.'/CMD_API_SHOW_USERS?json=yes';
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

            // ubah dari json menjadi array php
            $body = $body ? json_decode($body, true) : [];

            return $body;
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage(), 'url' => $url];
        }
    }

    public function getUserDetails($username)
    {
        $url = $this->server->url.'/api/users/'.$username.'/config';
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

            // ubah dari json menjadi array php
            $body = $body ? json_decode($body, true) : [];

            return $body;
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage(), 'url' => $url];
        }
    }
}
