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

    //get couriers list
    public function getCouriers(Request $request, OngkirVDServices $services)
    {
        $params = $request->all();
        $result = $services->getCouriers($params);

        return response()->json($result);
    }

    //get analytics
    public function getAnalytics(Request $request, OngkirVDServices $services)
    {
        $params = $request->all();
        $result = $services->getAnalytics($params);

        return response()->json($result && $result['data'] ? $result['data'] : []);
    }

    //get shipping log chart
    public function getShippingLogChart(Request $request, OngkirVDServices $services)
    {
        $params = $request->all();
        $result = $services->getShippingLogChart($params);

        return response()->json($result);
    }
}
