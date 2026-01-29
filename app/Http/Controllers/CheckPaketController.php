<?php

namespace App\Http\Controllers;

use App\Services\DirectAdminService;
use App\Services\WHMCSCustomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CheckPaketController extends Controller
{
    public function __invoke(Request $request, DirectAdminService $whm, WHMCSCustomService $whmcs): JsonResponse
    {
        $products = $whmcs->getProducts() ?? [];

        $productPackageCodes = collect($products)
            ->pluck('configoption1')
            ->filter()
            ->map(fn ($code) => strtolower(trim($code)));

        $packages = collect($whm->getPackages());

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

        // PAGINASI
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);
        $sliced = $results->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $sliced,
            $results->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }
}
