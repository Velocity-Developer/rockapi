<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DirectAdminService;
use App\Services\WHMCSService;
use Illuminate\Http\JsonResponse;

class CheckPaketController extends Controller
{
  public function __invoke(DirectAdminService $whm, WHMCSService $whmcs): JsonResponse
  {
    $products = $whmcs->getProducts();
    $packages = collect($whm->getPackages())->pluck('name')->map(fn($v) => strtolower($v));

    $results = [];

    foreach ($products as $product) {
      $name = strtolower($product['name']);
      $price = $product['pricing']['USD']['monthly'] ?? 'N/A';

      $exists = $packages->contains($name);

      $results[] = [
        'product_name' => $product['name'],
        'exists_in_whm' => $exists,
        'price_usd_monthly' => $price,
        'status' => $exists ? 'OK' : 'NOT FOUND IN WHM',
      ];
    }

    return response()->json(
      [
        'products' => $products,
        'packages' => $packages,
        'results' => $results
      ]
    );
  }
}
