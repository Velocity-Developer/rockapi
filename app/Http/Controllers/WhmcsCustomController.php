<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WHMCSCustomService;
use Illuminate\Http\JsonResponse;

class WhmcsCustomController extends Controller
{
    public function expired_domains(Request $request, WHMCSCustomService $whmcs): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));
        $domains = $whmcs->getDomainsExpiry($month) ?? [];
        return response()->json($domains);
    }
}
