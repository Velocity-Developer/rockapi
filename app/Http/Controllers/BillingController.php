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

        //get cs_main_project
        $query = CsMainProject::with('webhost', 'webhost.paket', 'karyawans:nama');

        // Check if order_by is 'webhost.hpads'
        if ($order_by == 'webhost.hpads') {
            $query->join('tb_webhosts', 'cs_main_projects.webhost_id', '=', 'webhosts.id')
                ->orderBy('webhosts.hpads', $order);
        } else {
            $query->orderBy($order_by, $order);
        }

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
        $nama_paket = $request->input('paket');
        if ($nama_paket) {
            $query->whereHas('webhost.paket', function ($query) use ($nama_paket) {
                $query->where('paket', 'like', '%' . $nama_paket . '%');
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

        //filter by trf
        $trf = $request->input('trf');
        if ($trf) {
            $query->where('trf', 'like', '%' . $trf . '%');
        }

        //filter by webhost.hp
        $hp = $request->input('hp');
        if ($hp) {
            $query->whereHas('webhost', function ($query) use ($hp) {
                $query->where('hp', 'like', '%' . $hp . '%');
            });
        }

        //filter by webhost.telegram
        $telegram = $request->input('telegram');
        if ($telegram) {
            $query->whereHas('webhost', function ($query) use ($telegram) {
                $query->where('telegram', 'like', '%' . $telegram . '%');
            });
        }

        //filter by webhost.hpads
        $hpads = $request->input('hpads');
        if ($hpads) {
            $query->whereHas('webhost', function ($query) use ($hpads) {
                $query->where('hpads', 'like', '%' . $hpads . '%');
            });
        }

        //filter by webhost.wa
        $wa = $request->input('wa');
        if ($wa) {
            $query->whereHas('webhost', function ($query) use ($wa) {
                $query->where('wa', 'like', '%' . $wa . '%');
            });
        }

        //filter by webhost.email
        $email = $request->input('email');
        if ($email) {
            $query->whereHas('webhost', function ($query) use ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            });
        }

        $data = $query->paginate($per_page);

        //return json
        return response()->json($data);
    }
}
