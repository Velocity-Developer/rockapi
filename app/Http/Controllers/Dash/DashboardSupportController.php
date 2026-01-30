<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardSupport;
use Illuminate\Http\Request;

class DashboardSupportController extends Controller
{
    //
    public function paket()
    {
        $DashboardSupport = new DashboardSupport;
        $result = $DashboardSupport->project_support_paket();
        return response()->json($result);
    }

    //
    public function jurnal_daily()
    {
        $DashboardSupport = new DashboardSupport;
        $result = $DashboardSupport->journal_support_daily();
        return response()->json($result);
    }

    public function dashboard_counts()
    {
        $DashboardSupport = new DashboardSupport;
        $result = $DashboardSupport->dashboard_counts();
        return response()->json($result);
    }
}
