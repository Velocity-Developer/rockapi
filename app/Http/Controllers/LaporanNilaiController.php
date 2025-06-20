<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CsMainProject;

class LaporanNilaiController extends Controller
{
    public function index(Request $request)
    {
        [$tahun, $bulan] = explode('-', $request->input('bulan'));

        $jenisProject = $request->input('jenis_project');
        $results = ['users' => [], 'data' => []];

        // Fetch valid webdeveloper users
        $users = User::whereHas('roles', fn($query) =>
        $query->where('name', 'webdeveloper'))
            ->where('status', 'active')
            ->whereNotIn('name', ['webdeveloper', 'Web Custom', 'Web Biasa'])
            ->select('id', 'name', 'avatar')
            ->get();

        foreach ($users as $user) {
            $results['users'][$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'total' => 0
            ];
            $results['data'][$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'projects' => []
            ];
        }

        // CsMainProject
        $CsMainProject = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket',
            'webhost.paket:id_paket,paket',
            'wm_project:id_wm_project,id_karyawan,user_id,id,date_mulai,date_selesai,catatan,status_multi,webmaster',
            'wm_project.user:id,name,avatar'
        ])
            ->select('id', 'id_webhost', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh')
            ->whereIn('jenis', [
                'Jasa Update Web',
                'Pembuatan',
                'Pembuatan apk custom',
                'Pembuatan Tanpa Domain',
                'Pembuatan Tanpa Hosting',
                'Pembuatan Tanpa Domain+Hosting',
                'Redesign'
            ])
            ->when($jenisProject == 0, function ($q) {
                $q->where(function ($query) {
                    $query->where('dikerjakan_oleh', 'LIKE', '%,12%')
                        ->orWhere('dikerjakan_oleh', 'LIKE', '%,10%');
                });
            })
            ->when($jenisProject != 0, function ($q) use ($jenisProject) {
                $q->where('dikerjakan_oleh', 'LIKE', "%,$jenisProject%");
            })
            ->whereHas('wm_project', function ($q) use ($bulan, $tahun) {
                $q->where(function ($query) use ($bulan, $tahun) {
                    $query->whereMonth('date_selesai', $bulan)
                        ->whereYear('date_selesai', $tahun)
                        ->orWhereNull('date_selesai');
                });
            })
            ->orderBy('tgl_masuk', 'desc')
            ->get();
        $results['raw'] = $CsMainProject;

        //susun data
        foreach ($CsMainProject as $project) {
            $user_id = $project->wm_project->user_id;
            if (isset($results['users'][$user_id])) {
                $results['users'][$user_id]['total']++;
                $results['data'][$user_id]['projects'][] = $project;
            }
        }

        $results['users'] = array_values($results['users']);

        return response()->json($results);
    }
}
