<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhost;

class BillDataWebController extends Controller
{
    //index
    public function index(Request $request)
    {
        $per_page           = $request->input('per_page', 50);
        $order_by           = $request->input('order_by', 'tgl_mulai');
        $order              = $request->input('order', 'desc');

        //get webhost
        $query = Webhost::with('paket:id_paket,paket');

        $query->orderBy($order_by, $order);

        // Apply date filter if both start and end dates are provided
        $tgl_masuk_start    = $request->input('tgl_masuk_start');
        $tgl_masuk_end      = $request->input('tgl_masuk_end');
        if ($tgl_masuk_start && $tgl_masuk_end) {
            $query->whereBetween('tgl_mulai', [$tgl_masuk_start, $tgl_masuk_end]);
        }

        //filter by nama_web
        $nama_web = $request->input('nama_web');
        if ($nama_web) {
            $query->where('nama_web', 'like', '%' . $nama_web . '%');
        }

        //filter by paket
        $paket = $request->input('paket');
        if ($paket) {
            $query->where('paket', 'like', '%' . $paket . '%');
        }
        //filter by hp
        $hp = $request->input('hp');
        if ($hp) {
            $query->where('hp', 'like', '%' . $hp . '%');
        }
        //filter by hpads
        $hpads = $request->input('hpads');
        if ($hpads) {
            $query->where('hpads', 'like', '%' . $hpads . '%');
        }
        //filter by wa
        $wa = $request->input('wa');
        if ($wa) {
            $query->where('wa', 'like', '%' . $wa . '%');
        }
        //filter by email
        $email = $request->input('email');
        if ($email) {
            $query->where('email', 'like', '%' . $email . '%');
        }

        $data = $query->paginate($per_page);

        //return json
        return response()->json($data);
    }
}
