<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LaporanNilaiController extends Controller
{
    public function index(Request $request)
    {
        [$tahun, $bulan] = explode('-', $request->input('bulan'));
        $jenisProject = $request->input('jenis_project');
        $results = ['users' => [], 'data' => []];

        $now = now();
        $isCurrentMonth = (
            (int)$bulan === (int)$now->month &&
            (int)$tahun === (int)$now->year
        );

        // Ambil user webdeveloper beserta project yang sudah difilter
        $users = User::whereHas('roles', fn($query) => $query->where('name', 'webdeveloper'))
            ->where('status', 'active')
            ->whereNotIn('name', ['webdeveloper', 'Web Custom', 'Web Biasa'])
            ->select('id', 'name', 'avatar')
            ->with(['wm_project' => function ($q) use ($bulan, $tahun, $jenisProject, $isCurrentMonth) {
                $q->select([
                    'id',
                    'id_wm_project',
                    'id_karyawan',
                    'user_id',
                    'date_mulai',
                    'date_selesai',
                    'status_multi',
                    'status_project',
                    'webmaster',
                ])->whereHas('cs_main_project', function ($query) use ($jenisProject) {
                    $query->whereIn('jenis', [
                        'Jasa Update Web',
                        'Pembuatan',
                        'Pembuatan apk custom',
                        'Pembuatan Tanpa Domain',
                        'Pembuatan Tanpa Hosting',
                        'Pembuatan Tanpa Domain+Hosting',
                        'Redesign',
                        'Pembuatan web konsep',
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
                    ->where(function ($query) use ($bulan, $tahun, $isCurrentMonth) {
                        // 1️⃣ Project selesai di bulan laporan
                        $query->where(function ($q) use ($bulan, $tahun) {
                            $q->whereMonth('date_selesai', $bulan)
                                ->whereYear('date_selesai', $tahun);
                        });
                        // 2️⃣ Project masih berjalan → HANYA jika bulan sekarang
                        if ($isCurrentMonth) {
                            $query->orWhere(function ($q) {
                                $q->whereNull('date_selesai')
                                    ->orWhere('date_selesai', '');
                            });
                        }
                    })
                    // ✅ Tambahan filter supaya harus ada date_mulai
                    ->whereNotNull('date_mulai')
                    ->where('date_mulai', '<>', '')
                    ->whereDate('date_mulai', '>=', now()->subYear())
                    ->with(['cs_main_project' => function ($q) {
                        $q->select('id', 'jenis', 'deskripsi', 'tgl_deadline', 'dikerjakan_oleh', 'id_webhost', 'dibayar')
                            ->with('webhost:id_webhost,id_paket,nama_web', 'webhost.paket:id_paket,paket', 'cs_main_project_info');
                    }]);
            }])
            ->get();

        $total_all_dibayar = 0;

        // Hitung dulu total_all_dibayar
        foreach ($users as $user) {
            $total_all_dibayar += $user->wm_project->sum(function ($project) {
                return $project->cs_main_project ? $project->cs_main_project->dibayar : 0;
            });
        }

        foreach ($users as $user) {

            $total_selesai = $user->wm_project->where('date_selesai', '!=', null)->where('date_selesai', '!=', '')->count();
            $total_progress = $user->wm_project->filter(function ($project) {
                return $project->date_selesai === null || $project->date_selesai === '';
            })->count();

            // Hitung total dibayar dari cs_main_project
            $total_dibayar = $user->wm_project->sum(function ($project) {
                return $project->cs_main_project ? $project->cs_main_project->dibayar : 0;
            });

            // hitung total bobot
            $total_bobot = $user->wm_project->sum(function ($project) {
                $bobot = 0;
                $dikerjakan_oleh = $project->cs_main_project->dikerjakan_oleh ?? null;
                if (str_contains($dikerjakan_oleh, ',12')) {
                    $bobot = 2;
                } elseif (str_contains($dikerjakan_oleh, ',10')) {
                    $bobot = 0.3;
                }
                $waktu_plus = $project->cs_main_project->cs_main_project_info->waktu_plus ?? 0;
                return $bobot + $waktu_plus;
            });

            // Ubah ke persen (hindari pembagian nol)
            $percent_dibayar = $total_all_dibayar > 0
                ? round(($total_dibayar / $total_all_dibayar) * 100, 2)
                : 0;

            $results['users'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'total' => $user->wm_project->count(),
                'selesai' => $total_selesai,
                'progress' => $total_progress,
                'total_dibayar' => $percent_dibayar,
                'total_bobot' => $total_bobot,
            ];

            //loop projects
            $wm_project = [];
            foreach ($user->wm_project as $project) {

                $bobot = 0;
                $dikerjakan_oleh = $project->cs_main_project->dikerjakan_oleh ?? null;
                if (str_contains($dikerjakan_oleh, ',12')) {
                    $bobot = 2;
                } elseif (str_contains($dikerjakan_oleh, ',10')) {
                    $bobot = 0.3;
                }
                $waktu_plus = $project->cs_main_project->cs_main_project_info->waktu_plus ?? 0;
                $bobot += $waktu_plus;

                $wm_project[] = [
                    'cs_main_project' => [
                        'webhost' => $project->cs_main_project->webhost,
                        'dikerjakan_oleh' => $project->cs_main_project->dikerjakan_oleh,
                        'jenis' => $project->cs_main_project->jenis,
                        'cs_main_project_info' => [
                            'bobot' => $bobot,
                            'waktu_plus' => $project->cs_main_project->cs_main_project_info->waktu_plus ?? 0
                        ],
                    ],
                    'dikerjakan_oleh' => $dikerjakan_oleh,
                    'bobot' => $bobot,
                    'status_multi'  => $project->status_multi,
                    'status_project'  => $project->status_project,
                    'date_mulai_formatted'  => $project->date_mulai_formatted,
                    'date_selesai_formatted'  => $project->date_selesai_formatted,
                ];
            }

            $results['data'][$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'projects' => $wm_project,
                'total_dibayar' => $total_dibayar,
            ];
        }

        return response()->json($results);
    }
}
