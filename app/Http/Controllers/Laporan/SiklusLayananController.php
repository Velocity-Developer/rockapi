<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\TanggalFormatterHelper;
use App\Services\ConvertDataLamaService;
use App\Models\CsMainProject;
use App\Models\Webhost;

class SiklusLayananController extends Controller
{
    private $jenis_pembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting'
    ];

    public function index(Request $request)
    {
        $results = [];
        $date = $request->input('bulan');
        $date = Carbon::parse($date);
        $bulan = $date->format('m');
        $tahun = $date->format('Y');
        //date 1 tahun lalu
        $date_1_tahun_lalu = $date->subYear();
        $bulan_lalu = $date_1_tahun_lalu->format('m');
        $tahun_lalu = $date_1_tahun_lalu->format('Y');

        $results['date'] = [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulan_lalu' => $bulan_lalu,
            'tahun_lalu' => $tahun_lalu,
        ];

        // get webhost dengan CsMainProject.jenis = jenis_pembuatan
        // dan CsMainProject.tgl_masuk = $tahun . '-' . $bulan
        $webhosts = Webhost::with(['csMainProjects' => function ($query) use ($bulan, $tahun, $bulan_lalu, $tahun_lalu) {
            $query->where(function ($q) use ($bulan_lalu, $tahun_lalu) {
                $q->whereIn('jenis', $this->jenis_pembuatan)
                    ->whereMonth('tgl_masuk', $bulan_lalu)
                    ->whereYear('tgl_masuk', $tahun_lalu);
            })->orWhere(function ($q) use ($bulan, $tahun) {
                $q->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan)
                    ->whereYear('tgl_masuk', $tahun);
            });
        }])
            ->whereHas('csMainProjects', function ($query) use ($bulan_lalu, $tahun_lalu) {
                $query->whereIn('jenis', $this->jenis_pembuatan)
                    ->whereMonth('tgl_masuk', $bulan_lalu)
                    ->whereYear('tgl_masuk', $tahun_lalu);
            })
            ->limit(1000)
            ->get();

        $results['raw'] = $webhosts;

        //loop webhost
        foreach ($webhosts as $webhost) {
            // loop projects
            foreach ($webhost->csMainProjects as $project) {
                $dibayar    = $project->dibayar;
                $jenis      = $project->jenis;

                //jika jenis = perpanjangan
                if (in_array($jenis, $this->jenis_pembuatan)) {
                    $jenis = "Pembuatan";
                }

                if (!isset($results['data'][$jenis])) {
                    $results['data'][$jenis] = [
                        'jenis'     => $jenis,
                        'projects'  => [],
                        'total'     => 0,
                        'nominal'   => 0,
                        'webhosts'  => [],
                    ];
                }

                $results['data'][$jenis]['webhosts'][$webhost->id_webhost] = $webhost;
                $results['data'][$jenis]['jenis'] = $jenis;
                $results['data'][$jenis]['projects'][] = $project;
                $results['data'][$jenis]['total']++;
                $results['data'][$jenis]['nominal'] += $dibayar;
            }
        }

        return response()->json($results);
    }
}
