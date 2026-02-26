<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WHMCSCustomService;
use Illuminate\Http\JsonResponse;

class WhmcsCustomController extends Controller
{
    public function expired_domains(Request $request, WHMCSCustomService $whmcs): JsonResponse
    {
        $date = (int) $request->input('date', date('Y-m-d'));

        $domains = $whmcs->getDomainsExpiry($date) ?? [];
        return response()->json($domains);
    }
}
