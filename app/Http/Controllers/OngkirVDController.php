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

        return response()->json([
            'data' => $result,
        ]);
    }
}
