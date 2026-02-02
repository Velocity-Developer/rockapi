<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsSupport;
use Illuminate\Http\Request;

class DashboardSupportController extends Controller
{
    //
    public function paket()
    {
        $AnalyticsSupport = new AnalyticsSupport;
        $result = $AnalyticsSupport->project_support_paket();
        return response()->json($result);
    }

    //
    public function jurnal_daily()
    {
        $AnalyticsSupport = new AnalyticsSupport;
        $result = $AnalyticsSupport->journal_support_daily();
        return response()->json($result);
    }

    public function dashboard_counts()
    {
        $AnalyticsSupport = new AnalyticsSupport;
        $result = $AnalyticsSupport->dashboard_counts();
        return response()->json($result);
    }
}
