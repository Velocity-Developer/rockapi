<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WHMCSCustomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class WhmcsCustomController extends Controller
{
    public function expired_domains(Request $request, WHMCSCustomService $whmcs): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));
        $domains = $whmcs->getDomainsExpiry($month) ?? [];
        return response()->json($domains);
    }

    public function expired_month(Request $request, WHMCSCustomService $whmcs): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));

        $cacheKey = 'whmcs_expired_month_data_' . $month;

        $domains = Cache::remember($cacheKey, now()->addHours(5), function () use ($whmcs, $month) {
            $gets = $whmcs->getExpiredMonth($month) ?? [];
            return $gets ? array_values($gets['data']) : [];
        });

        return response()->json($domains);
    }
}
