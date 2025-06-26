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
        );

        //order by
        $order_by           = $request->input('order_by', 'tgl_deadline');
        $order              = $request->input('order', 'desc');
        $query->orderBy($order_by, $order);

        $per_page = $request->input('per_page', 50);
        $data = $query->paginate($per_page);

        return response()->json($data);
    }
}
