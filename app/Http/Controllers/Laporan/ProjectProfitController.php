<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CsMainProject;

class ProjectProfitController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = [];

        //query
        $query = CsMainProject::with('webhost:id_webhost,nama_web,id_paket', 'webhost.paket:id_paket,paket');

        //filter by month
        $month_start = $request->input('month_start');
        $month_end = $request->input('month_end');
        if ($month_start && $month_end) {
            $query->whereBetween('tgl_masuk', [$month_start, $month_end]);
        }

        $cs_mainproject = $query->get();

        // ambil data dan group by webhost.nama_web
        $data = $query->get()->groupBy(function ($item) {
            return optional($item->webhost)->nama_web ?: 'Tanpa Webhost';
        });


        return response()->json([
            'data' => $data,
            'raw' => $cs_mainproject,
        ]);
    }
}
