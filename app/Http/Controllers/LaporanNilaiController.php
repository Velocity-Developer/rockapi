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

        // Ambil user webdeveloper beserta project yang sudah difilter
        $users = User::whereHas('roles', fn($query) =>
        $query->where('name', 'webdeveloper'))
            ->where('status', 'active')
            ->whereNotIn('name', ['webdeveloper', 'Web Custom', 'Web Biasa'])
            ->select('id', 'name', 'avatar')
            ->with(['wm_project' => function ($q) use ($bulan, $tahun, $jenisProject) {
                $q->whereHas('cs_main_project', function ($query) use ($jenisProject) {
                    $query->whereIn('jenis', [
                        'Jasa Update Web',
                        'Pembuatan',
                        'Pembuatan apk custom',
                        'Pembuatan Tanpa Domain',
                        'Pembuatan Tanpa Hosting',
                        'Pembuatan Tanpa Domain+Hosting',
                        'Redesign'
                    ]);
                    if ($jenisProject == 0) {
                        $query->where(function ($sub) {
                            $sub->where('dikerjakan_oleh', 'LIKE', '%,12%')
                                ->orWhere('dikerjakan_oleh', 'LIKE', '%,10%');
                        });
                    } else {
                        $query->where('dikerjakan_oleh', 'LIKE', "%,$jenisProject%");
                    }
                })
                    ->where(function ($query) use ($bulan, $tahun) {
                        $query->whereMonth('date_selesai', $bulan)
                            ->whereYear('date_selesai', $tahun)
                            ->orWhereNull('date_selesai');
                    })
                    ->with(['cs_main_project' => function ($q) {
                        $q->select('id', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh', 'id_webhost');
                    }]);
            }])
            ->get();

        foreach ($users as $user) {
            $results['users'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'total' => $user->wm_project->count()
            ];

            // Group project berdasarkan jenis
            $grouped = $user->wm_project
                ->filter(fn($wm) => $wm->cs_main_project)
                ->groupBy(fn($wm) => $wm->cs_main_project->jenis)
                ->map(function ($projects) {
                    // Ambil cs_main_project saja
                    return $projects->map(fn($wm) => $wm->cs_main_project)->values();
                });

            $results['data'][$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'projects' => $grouped
            ];
        }

        return response()->json($results);
    }
}
