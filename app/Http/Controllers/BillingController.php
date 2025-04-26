<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhost;
use App\Models\CsMainProject;

class BillingController extends Controller
{
    //index
    public function index(Request $request)
    {
        $per_page           = $request->input('per_page', 50);
        $order_by           = $request->input('order_by', 'tgl_masuk');
        $order              = $request->input('order', 'desc');
        $tgl_masuk_start    = $request->input('tgl_masuk_start');
        $tgl_masuk_end      = $request->input('tgl_masuk_end');

        //get cs_main_project
        $query = CsMainProject::with('webhost', 'webhost.paket')
            ->orderBy($order_by, $order);

        // Apply date filter if both start and end dates are provided
        if ($tgl_masuk_start && $tgl_masuk_end) {
            $query->whereBetween('tgl_masuk', [$tgl_masuk_start, $tgl_masuk_end]);
        }

        $data = $query->paginate($per_page);

        //return json
        return response()->json($data);
    }
}
