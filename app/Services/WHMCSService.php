<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WHMCSService
{
  public function getProducts(): array
  {
    $response = Http::asForm()->get(config('services.whmcs.url'), [
      'responsetype' => 'json',
      'timeout'      => '30',
    ]);
    return $response->json('packages') ?? [];
  }
}
