<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WHMCSCustomService
{
  public function getProducts(): array
  {
    $response = Http::asForm()->get(config('services.whmcs.custom_url'), [
      'responsetype' => 'json',
      'timeout'      => '30',
    ]);
    return $response->json('packages') ?? [];
  }
}
