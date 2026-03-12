<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WHMCSCustomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Services\WHMCSSyncServices;
use Carbon\Carbon;

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
            return $gets && isset($gets['data']) ? array_values($gets['data']) : [];
        });

        return response()->json($domains);
    }

    public function re_sync_domain_hosting(Request $request, WHMCSCustomService $whmcs): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));

        $cacheKey = "whmcs_resync_domain_hosting_{$month}";

        $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($month) {

            $start_date = Carbon::createFromFormat('Y-m', $month)
                ->startOfMonth()
                ->toDateString();

            $end_date = Carbon::createFromFormat('Y-m', $month)
                ->addMonths(3)
                ->endOfMonth()
                ->toDateString();

            // mengambil data domain expired dari WHMCS
            $domains = (new WHMCSSyncServices())->syncDomainExpired($start_date, $end_date);

            // mengambil data hosting expired dari WHMCS
            $hostings = (new WHMCSSyncServices())->syncHostingExpired($start_date, $end_date);

            return [
                'domains' => count($domains),
                'hostings' => count($hostings),
            ];
        });

        return response()->json($data);
    }
}
