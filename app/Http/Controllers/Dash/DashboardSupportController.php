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
}
