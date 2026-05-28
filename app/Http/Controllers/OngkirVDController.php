<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OngkirVDServices;

class OngkirVDController extends Controller
{
    //get shipping logs
    public function getShippingLogs(Request $request, OngkirVDServices $services)
    {
        $params = $request->all();
        $result = $services->getShippingLogs($params);

        return response()->json($result);
    }

    //get kodepos logs
    public function getKodePosLogs(Request $request, OngkirVDServices $services)
    {
        $params = $request->all();
        $result = $services->getKodePosLogs($params);

        return response()->json($result);
    }
}
