<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsMainProject;
use App\Models\Quality;

class ProjectListController extends Controller
{
    public function index(Request $request)
    {

        $per_page           = $request->input('per_page', 50);
        $order_by           = $request->input('order_by', 'tgl_masuk');
        $order              = $request->input('order', 'desc');

        //get cs_main_project
        $query = CsMainProject::with(
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
            'wm_project:id_wm_project,id_karyawan,id,date_mulai,date_selesai,catatan,status_multi,webmaster'
        );

        $query->select('id', 'id_webhost', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh');

        //order by
        $query->orderBy($order_by, $order);

        //filter jenis
        $query->where('jenis', '!=', 'perpanjangan');

        //filter jenis_project
        if ($request->input('jenis_project')) {
            $query->where('dikerjakan_oleh', 'LIKE', '%,' . $request->input('jenis_project') . '%');
        }

        // Apply date filter if both start and end dates are provided
        $tgl_masuk_start    = $request->input('tgl_masuk_start');
        $tgl_masuk_end      = $request->input('tgl_masuk_end');
        if ($tgl_masuk_start && $tgl_masuk_end) {
            $query->whereBetween('tgl_masuk', [$tgl_masuk_start, $tgl_masuk_end]);
        }

        $data = $query->paginate($per_page);

        return response()->json($data);
    }
}
