<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WHMCSService
{
  public function getProducts(): array
  {
    $response = Http::asForm()->post(config('services.whmcs.url'), [
      'identifier'   => config('services.whmcs.identifier'),
      'secret'       => config('services.whmcs.secret'),
      'action'       => 'GetProducts',
      'responsetype' => 'json',
      'timeout'      => '30',
    ]);

    return $response->json('products.product') ?? [];
  }

  public function getServers(): array
  {
    $response = Http::asForm()->post(config('services.whmcs.url'), [
      'identifier'   => config('services.whmcs.identifier'),
      'secret'       => config('services.whmcs.secret'),
      'action'       => 'GetServers',
      'responsetype' => 'json',
    ]);

    return $response->json('servers.server') ?? [];
  }
}
