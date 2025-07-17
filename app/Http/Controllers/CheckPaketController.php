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
    $products = $whmcs->getProducts() ?? [];

    // Ambil semua configoption1 (kode paket) dan buat lowercase
    $productPackageCodes = collect($products)
      ->pluck('configoption1')
      ->filter() // hilangkan null atau kosong
      ->map(fn($code) => strtolower(trim($code)));

    // Ambil semua nama paket dari DirectAdmin
    $packages = collect($whm->getPackages());

    // Cek apakah setiap paket ada di WHMCS
    $results = $packages->map(function ($package) use ($productPackageCodes) {
      $normalized = strtolower(trim($package));
      $exists = $productPackageCodes->contains($normalized);

      return [
        'package_name' => $package,
        'exists_in_whmcs' => $exists,
        'status' => $exists ? 'OK' : 'NOT REGISTERED IN WHMCS',
        'icon' => $exists ? '✅' : '❌',
      ];
    });

    return response()->json([
      'results' => $results,
      // 'products' => $products,
      // 'packages' => $packages->toArray(),
    ]);
  }
}
