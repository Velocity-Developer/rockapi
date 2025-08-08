<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
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
        $date_y = $request->input('bulan');
        $date_y = Carbon::parse($date_y);
        $date_1_tahun_lalu = $date_y->subYear();
        $bulan_lalu = $date_1_tahun_lalu->format('m');
        $tahun_lalu = $date_1_tahun_lalu->format('Y');

        $results['date'] = [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulan_lalu' => $bulan_lalu,
            'tahun_lalu' => $tahun_lalu,
        ];

        /**
         * Perpanjang Bulan Ini
         * mengambil data webhost yang memiliki cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan ini
         */
        $perpanjang_bulan_ini = Webhost::with('CsMainProject')
            ->whereHas('cs_main_project', function ($query) use ($bulan, $tahun) {
                $query->where('jenis', 'Perpanjangan')
                    ->whereMonth('tgl_masuk', $bulan)
                    ->whereYear('tgl_masuk', $tahun);
            })
            ->get();
        $total_perpanjang_bulan_ini = $perpanjang_bulan_ini->count();
        $results['meta'][] = $perpanjang_bulan_ini;

        $results['data'] = [
            // 'perpanjang'        => [
            //     'label'         => 'Perpanjang',
            //     'total'         => $total_perpanjang_bulan_ini,
            //     'nominal'       => $perpanjang_bulan_ini->sum('dibayar'),
            //     'webhosts'      => $perpanjang_bulan_ini->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
            //         $webhost = clone $projects->first()->webhost;
            //         $webhost->cs_main_project = $projects->each(function ($project) {
            //             unset($project->webhost);
            //         })->values();
            //         return $webhost;
            //     })->values()
            // ],
            // 'perpanjang_baru'   => [
            //     'label'         => 'Perpanjang Baru',
            //     'total'         => $perpanjang_baru->count(),
            //     'nominal'       => $perpanjang_baru->sum('dibayar'),
            //     'webhosts'      => $perpanjang_baru->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
            //         $webhost = clone $projects->first()->webhost;
            //         $webhost->cs_main_project = $projects->each(function ($project) {
            //             unset($project->webhost);
            //         })->values();
            //         return $webhost;
            //     })->values()
            // ],
            // 'tidak_perpanjang'  => [
            //     'label'         => 'Tidak Perpanjang',
            //     'total'         => $tidak_perpanjang->count(),
            //     'nominal'       => $tidak_perpanjang->sum('dibayar'),
            //     'webhosts'      => $tidak_perpanjang->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
            //         $webhost = clone $projects->first()->webhost;
            //         $webhost->cs_main_project = $projects->each(function ($project) {
            //             unset($project->webhost);
            //         })->values();
            //         return $webhost;
            //     })->values()
            // ]
        ];

        return response()->json($results);
    }
}
