<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\CsMainProject;
use App\Models\HargaDomain;
use App\Models\BiayaAds;

class NetProfitController extends Controller
{
    //
    public function index(Request $request)
    {

        $dari = $request->input('bulan_dari'); //format = YYYY-MMM
        //dapatkan hari pertama dari bulan $dari
        $dari = Carbon::parse($dari)->startOfMonth()->format('Y-m-d');
        $sampai = $request->input('bulan_sampai'); //format = YYYY-MMM
        //dapatkan hari terakhir dari bulan $sampai
        $sampai = Carbon::parse($sampai)->endOfMonth()->format('Y-m-d');

        $jenis_pembuatan = [
            'Pembuatan',
            'Pembuatan apk',
            'Pembuatan apk custom',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting'
        ];

        //query
        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
        ]);

        //jenis = jenis_pembuatan
        $query->whereIn('jenis', $jenis_pembuatan);

        //filter by tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);

        $raw_data = $query->get();

        //kelola data
        $data = [];
        foreach ($raw_data as $row) {
            $data[$row->id_wm_project]['id_wm_project'] = $row->id_wm_project;
            $data[$row->id_wm_project]['id_webhost'] = $row->id_webhost;
            $data[$row->id_wm_project]['id_paket'] = $row->id_paket;
        }

        return response()->json([
            'dari'          => $dari,
            'sampai'        => $sampai,
            'raw_data'      => $raw_data,
            'data'          => $data
        ]);
    }
}
