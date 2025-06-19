<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CsMainProject;

class LaporanNilaiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->input('bulan');
        //pecah bulan
        $bulan = explode('-', $bulan);
        $bulan = $bulan[1];
        $tahun = $bulan[0];

        $jenis_project = $request->input('jenis_project');

        $results = [];

        //get users, roles = webdeveloper, status = active, kecuali name : webdeveloper,Web Custom,Web Biasa
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'webdeveloper');
        })
            ->where('status', 'active')
            ->whereNotIn('name', ['webdeveloper', 'Web Custom', 'Web Biasa'])
            ->select('id', 'name', 'avatar')
            ->get();

        //susun data users
        foreach ($users as $i => $user) {
            $results['users'][$user->id] = [
                'id'    => $user->id,
                'name'  => $user->name,
                'avatar' => $user->avatar_url,
                'total' => 0,
            ];
            $results['data'][$user->id] = [
                'id'    => $user->id,
                'name'  => $user->name,
                'avatar' => $user->avatar_url,
                'projects'  => []
            ];
        }

        //get cs_main_project
        $query = CsMainProject::with(
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
            'wm_project:id_wm_project,id_karyawan,user_id,id,date_mulai,date_selesai,catatan,status_multi,webmaster',
            'wm_project.user:id,name,avatar',
        );

        $query->select('id', 'id_webhost', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh');

        //filter jenis
        $query->where('jenis', '!=', 'perpanjangan');

        //filter jenis_project
        if ($request->input('jenis_project')) {
            $query->where('dikerjakan_oleh', 'LIKE', '%,' . $jenis_project . '%');
        }

        //filter wm_project.date_mulai
        // $query->whereHas('wm_project', function ($query) use ($bulan, $tahun) {
        //     $query->whereMonth('date_mulai', $bulan)
        //         ->whereYear('date_mulai', $tahun);
        // });

        //limit
        $query->limit(100);

        //order by
        $query->orderBy('tgl_masuk', 'desc');

        //ambil data CsMainProject tiap user
        foreach ($results['users'] as $i => $user) {

            $id_user = $user['id'];

            //clone query
            $query_clone = $query->clone();

            //filter wm_project.user_id
            $query_clone->whereHas('wm_project', function ($query) use ($id_user) {
                $query->where('user_id', $id_user);
            });

            //ambil data
            $CsMainProject = $query_clone->get();

            //jika kosong, skip
            if ($CsMainProject->isEmpty()) {
                continue;
            }

            //hitung total
            $results['users'][$i]['total'] = $CsMainProject->count();

            if ($CsMainProject->isEmpty()) {
                $results['data'][$id_user]['projects'] = [];
                continue;
            }

            // $results['data'][$id_user]['projects'] = $CsMainProject;
        }

        $results['users'] = array_values($results['users']);

        return response()->json($results);
    }
}
