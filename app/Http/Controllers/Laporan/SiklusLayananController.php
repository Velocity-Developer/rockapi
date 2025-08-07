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
         * mengambil data cs_main_project
         * 1. mengambil data cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan ini
         * 2. mengambil data cs_main_project dengan jenis = $jenis_pembuatan dengan tgl_masuk di bulan tahun lalu
         * 3. mengambil data cs_main_project dengan jenis = 'Perpanjangan' dengan tgl_masuk di bulan tahun lalu
         */

        $perpanjang_bulan_ini = CsMainProject::with('webhost')
            ->where('jenis', 'Perpanjangan')
            ->whereMonth('tgl_masuk', $bulan)
            ->whereYear('tgl_masuk', $tahun)
            ->get();
        $total_perpanjang_bulan_ini = $perpanjang_bulan_ini->count();

        $pembuatan_tahun_lalu = CsMainProject::with('webhost')
            ->whereIn('jenis', $this->jenis_pembuatan)
            ->whereMonth('tgl_masuk', $bulan_lalu)
            ->whereYear('tgl_masuk', $tahun_lalu)
            ->get();
        $total_pembuatan_tahun_lalu = $pembuatan_tahun_lalu->count();

        $perpanjang_tahun_lalu = CsMainProject::with('webhost')
            ->where('jenis', 'Perpanjangan')
            ->whereMonth('tgl_masuk', $bulan_lalu)
            ->whereYear('tgl_masuk', $tahun_lalu)
            ->get();
        $total_perpanjang_tahun_lalu = $perpanjang_tahun_lalu->count();

        // Step 1: Pluck ID
        $idsBulanIni     = $perpanjang_bulan_ini->pluck('id_webhost');
        $idsTahunLalu    = $pembuatan_tahun_lalu->pluck('id_webhost');
        $idsPembuatanLalu = $perpanjang_tahun_lalu->pluck('id_webhost');

        // Step 2: Perpanjang Baru = Yang ada di bulan ini, tapi tidak ada di tahun lalu
        $idsPerpanjangBaru = $idsBulanIni->diff($idsTahunLalu);

        // Step 3: Tidak Perpanjang = Yang tahun lalu ada, tapi sekarang tidak ada
        $idsTidakPerpanjang = $idsTahunLalu->diff($idsBulanIni);

        // Step 4: Filter full data dari Collection aslinya
        $perpanjangBaru = $perpanjang_bulan_ini->whereIn('id_webhost', $idsPerpanjangBaru);
        $tidakPerpanjang = $perpanjang_tahun_lalu->whereIn('id_webhost', $idsTidakPerpanjang);

        // Step 5: (Opsional) Perpanjang Baru yang memang dari Pembuatan tahun lalu
        $idsPerpanjangBaruDariPembuatan = $idsPerpanjangBaru->intersect($idsPembuatanLalu);
        $perpanjangBaruDariPembuatan = $perpanjang_bulan_ini->whereIn('id_webhost', $idsPerpanjangBaruDariPembuatan);


        $results['meta'] = [
            'perpanjang_bulan_ini'  => [
                'label'         => 'Perpanjang bulan (' . $date->format('m-Y') . ')',
                'total'         => $total_perpanjang_bulan_ini,
                'nominal'       => $perpanjang_bulan_ini->sum('dibayar'),
                'data'          => $perpanjang_bulan_ini,
            ],
            'pembuatan_tahun_lalu'   => [
                'label'         => 'Pembuatan (' . $date_1_tahun_lalu->format('m-Y') . ')',
                'total'         => $total_pembuatan_tahun_lalu,
                'nominal'       => $pembuatan_tahun_lalu->sum('dibayar'),
                'data'          => $pembuatan_tahun_lalu,
            ],
            'perpanjang_tahun_lalu'  => [
                'label'         => 'Perpanjang (' . $date_1_tahun_lalu->format('m-Y') . ')',
                'total'         => $total_perpanjang_tahun_lalu,
                'nominal'       => $perpanjang_tahun_lalu->sum('dibayar'),
                'data'          => $perpanjang_tahun_lalu,
            ],
            // 'perpanjang_baru'  => [
            //     'label'         => 'Perpanjang baru',
            //     'total'         => $perpanjangBaru->count(),
            //     'nominal'       => 0,
            //     'data'          => $perpanjangBaru,
            // ],
            // 'tidak_perpanjang'  => [
            //     'label'         => 'Tidak Perpanjang',
            //     'total'         => $tidakPerpanjang->count(),
            //     'nominal'       => 0,
            //     'data'          => $tidakPerpanjang,
            // ],
        ];

        /**
         * 1. Jumlah perpanjang : Total semua perpanjang sesuai bulan yg dipilih
         * 2. Perpanjang baru : website baru yang tidak ada di data perpanjang tahun lalu
         * 3. Jumlah yang tidak perpanjang : website yang tahun lalu ada, tapi tahun ini tidak ada
         */

        // Perpanjang baru: perpanjangan bulan ini yang webhostnya tidak ada di perpanjangan tahun lalu
        $id_perpanjang_tahun_lalu = $perpanjang_tahun_lalu->pluck('id_webhost');
        $perpanjang_baru = $perpanjang_bulan_ini->whereNotIn('id_webhost', $id_perpanjang_tahun_lalu);

        // Tidak perpanjang: webhost dari pembuatan tahun lalu & perpanjangan tahun lalu, yang tidak ada di perpanjangan bulan ini
        $id_perpanjang_bulan_ini = $perpanjang_bulan_ini->pluck('id_webhost');
        $project_tahun_lalu = $pembuatan_tahun_lalu->merge($perpanjang_tahun_lalu)->unique('id_webhost');
        $tidak_perpanjang = $project_tahun_lalu->whereNotIn('id_webhost', $id_perpanjang_bulan_ini);

        $results['data'] = [
            'perpanjang'        => [
                'label'         => 'Perpanjang',
                'total'         => $total_perpanjang_bulan_ini,
                'nominal'       => $perpanjang_bulan_ini->sum('dibayar'),
                'webhosts'      => $perpanjang_bulan_ini->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
                    $webhost = clone $projects->first()->webhost;
                    $webhost->cs_main_project = $projects->each(function ($project) {
                        unset($project->webhost);
                    })->values();
                    return $webhost;
                })->values()
            ],
            'perpanjang_baru'   => [
                'label'         => 'Perpanjang Baru',
                'total'         => $perpanjang_baru->count(),
                'nominal'       => $perpanjang_baru->sum('dibayar'),
                'webhosts'      => $perpanjang_baru->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
                    $webhost = clone $projects->first()->webhost;
                    $webhost->cs_main_project = $projects->each(function ($project) {
                        unset($project->webhost);
                    })->values();
                    return $webhost;
                })->values()
            ],
            'tidak_perpanjang'  => [
                'label'         => 'Tidak Perpanjang',
                'total'         => $tidak_perpanjang->count(),
                'nominal'       => $tidak_perpanjang->sum('dibayar'),
                'webhosts'      => $tidak_perpanjang->whereNotNull('webhost')->groupBy('id_webhost')->map(function ($projects) {
                    $webhost = clone $projects->first()->webhost;
                    $webhost->cs_main_project = $projects->each(function ($project) {
                        unset($project->webhost);
                    })->values();
                    return $webhost;
                })->values()
            ]
        ];

        return response()->json($results);
    }
}
