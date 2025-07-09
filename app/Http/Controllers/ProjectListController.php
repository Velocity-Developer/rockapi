<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsMainProject;
use App\Models\Quality;
use Carbon\Carbon;

class ProjectListController extends Controller
{
    public function index(Request $request)
    {

        $per_page           = $request->input('per_page', 50);
        $order_by           = $request->input('order_by', 'tgl_deadline');
        $order              = $request->input('order', 'desc');

        //get cs_main_project
        $query = CsMainProject::with(
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
            'wm_project:id_wm_project,id_karyawan,user_id,id,date_mulai,date_selesai,catatan,status_multi,webmaster,status_project',
            'wm_project.user:id,name,avatar',
        );

        $query->select('id', 'id_webhost', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh');

        //order by
        $query->orderBy($order_by, $order);

        //jika ada request jenis, maka filter by jenis
        if ($request->input('jenis')) {
            // $query->where('jenis', 'like', '%' . $request->input('jenis') . '%');
            $query->where('jenis', '=', $request->input('jenis'));
        } else {
            $query->where('jenis', '!=', 'perpanjangan');
        }

        //filter webhost.nama_web
        if ($request->input('nama_web')) {
            $query->whereHas('webhost', function ($query) use ($request) {
                $query->where('nama_web', 'like', '%' . $request->input('nama_web') . '%');
            });
        }

        //filter webhost.paket
        if ($request->input('paket')) {
            $query->whereHas('webhost.paket', function ($query) use ($request) {
                $query->where('id_paket', $request->input('paket'));
            });
        }

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

        //filter by wm_project.status_multi
        $status_pengerjaan = $request->input('status_pengerjaan');
        if ($status_pengerjaan) {

            //jika status_pengerjaan = Belum dikerjakan, maka wm_project = null
            if ($status_pengerjaan == 'Belum dikerjakan') {
                $query->whereDoesntHave('wm_project');

                //tgl_masuk minimal satu tahun sebelum sekarang
                $query->whereYear('tgl_masuk', '>=', Carbon::now()->subYear());
            }

            //jika status_pengerjaan = selesai, maka wm_project = selesai
            else if ($status_pengerjaan == 'Selesai') {
                $query->whereHas('wm_project', function ($query) {
                    $query->where('status_multi', 'selesai');
                });
            }

            //jika status_pengerjaan = 'Dalam pengerjaan', maka wm_project = pending dan date_selesai = null/'' dan date_mulai != null dan user_id != null
            else if ($status_pengerjaan == 'Dalam pengerjaan') {
                $query->whereHas('wm_project', function ($query) {
                    $query->where('status_multi', 'pending')
                        ->whereNotNull('user_id')
                        ->whereNotNull('date_mulai')
                        ->where(function ($q) {
                            $q->whereNull('date_selesai')
                                ->orWhereRaw("TRIM(date_selesai) = ''");
                        });
                });
            }

            //jika status_pengerjaan = 'Proses koreksi', maka wm_project = pending dan date_selesai != null
            else if ($status_pengerjaan == 'Menunggu koreksi' || $status_pengerjaan == 'Proses koreksi' || $status_pengerjaan == 'Kurang konfirmasi') {
                $query->whereHas('wm_project', function ($query) use ($status_pengerjaan) {
                    $query->where('status_multi', 'pending')
                        ->whereNotNull('date_mulai')
                        ->whereNotNull('date_selesai')
                        ->Where('date_selesai', '!=', '')
                        ->where(function ($q) use ($status_pengerjaan) {
                            $q->where('status_project', $status_pengerjaan)
                                ->orWhereNull('status_project');
                        });
                });
            }
        }

        $data = $query->paginate($per_page);

        return response()->json($data);
    }
}
