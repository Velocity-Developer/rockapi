<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhost;
use App\Models\CsMainProject;
use App\Models\PmProject;
use Carbon\Carbon;

class ProjectManagerController extends Controller
{
    public function index(Request $request)
    {

        //get cs_main_project
        $query = CsMainProject::with(
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
            'wm_project:id_wm_project,user_id,id',
            'wm_project.user:id,name,avatar',
            'pm_project',
        );

        // Apply date filter if both start and end dates are provided
        $tgl_masuk_start    = $request->input('tgl_masuk_start');
        $tgl_masuk_end      = $request->input('tgl_masuk_end');
        if ($tgl_masuk_start && $tgl_masuk_end) {
            $query->whereBetween('tgl_masuk', [$tgl_masuk_start, $tgl_masuk_end]);
        }

        //filter by webhost.nama_web
        $nama_web = $request->input('nama_web');
        if ($nama_web) {
            $query->whereHas('webhost', function ($query) use ($nama_web) {
                $query->where('nama_web', 'like', '%' . $nama_web . '%');
            });
        }

        //filter by webhost.paket.nama_paket
        $id_paket = $request->input('paket');
        if ($id_paket) {
            $query->whereHas('webhost.paket', function ($query) use ($id_paket) {
                $query->where('id_paket', $id_paket);
            });
        }

        //filter by jenis
        $jenis = $request->input('jenis');
        if ($jenis) {
            $query->where('jenis', 'like', '%' . $jenis . '%');
        }

        //filter by deskripsi
        $deskripsi = $request->input('deskripsi');
        if ($deskripsi) {
            $query->where('deskripsi', 'like', '%' . $deskripsi . '%');
        }

        //order by
        $order_by           = $request->input('order_by', 'tgl_deadline');
        $order              = $request->input('order', 'desc');
        $query->orderBy($order_by, $order);

        $per_page   = $request->input('per_page', 50);
        $data       = $query->paginate($per_page);

        return response()->json($data);
    }
}
