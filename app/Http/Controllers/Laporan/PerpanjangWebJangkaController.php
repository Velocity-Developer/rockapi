<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\TanggalFormatterService;
use App\Services\ConvertDataLamaService;

use App\Models\CsMainProject;
use App\Models\HargaDomain;
use App\Models\BiayaAds;
use App\Models\Webhost;

class PerpanjangWebJangkaController extends Controller
{
    private $jenis_pembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting'
    ];

    /**
     * Index data for Laporan Perpanjang Web Jangka.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = [];
        $jangka_waktu = (int) $request->input('jangka') ?? 1;
        $bulan  = $request->input('bulan');

        //format bulan
        $formatter = new TanggalFormatterService();
        $bulan_formatted = $formatter->toIndonesianMonthYear($bulan);

        //get harga domain by bulan
        $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
        $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

        //get total biaya ads by bulan
        $biaya_ads = BiayaAds::where('bulan', $bulan)->sum('biaya');

        //jika kosong, maka jalankan fungsi service ConvertDataLamaService::handle_biaya_ads
        if (!$biaya_ads) {
            try {
                (new ConvertDataLamaService())->handle_biaya_ads();
                $biaya_ads = BiayaAds::where('bulan', $bulan)->sum('biaya');
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu',
            'webhost.paket:id_paket,paket',
            'webhost.csMainProjects' => function ($q) use ($jangka_waktu, $request) {
                if ($request->filled('bulan')) {
                    [$year, $month] = explode('-', $request->input('bulan'));
                    $start = Carbon::create($year, $month, 1)->startOfMonth();
                    $end = $start->copy()->addYears($jangka_waktu)->endOfMonth();

                    $q->whereBetween('tgl_masuk', [$start, $end])
                        ->orderBy('tgl_masuk');
                }
            }
        ]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($q) use ($bulan) {
            $q->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram']);
            //waktu chat pertama = $bulan
            $q->where('waktu', 'like', '%' . $bulan . '%');
        });

        // filter jenis pada cs_main_project
        $query->whereIn('jenis', $this->jenis_pembuatan);

        if ($request->filled('bulan')) {
            [$year, $month] = explode('-', $request->input('bulan'));
            $query->whereYear('tgl_masuk', $year)
                ->whereMonth('tgl_masuk', $month);
        }

        $data = $query->get();

        $total_profit_kotor = 0;
        $total_profit_bersih = 0;
        $total_profit_kotor_pembuatan = 0;
        $total_profit_bersih_pembuatan = 0;
        $total_order_pembuatan = 0;
        $data_order_jenis = [];

        $data->each(function ($project) use (
            &$total_profit_kotor,
            &$total_profit_bersih,
            &$harga_domain,
            &$total_profit_kotor_pembuatan,
            &$total_profit_bersih_pembuatan,
            &$total_order_pembuatan,
            &$data_order_jenis
        ) {

            $webhost = $project->webhost;

            //waktu chat pertama
            $waktu_chat_pertama = $webhost->waktu;
            $waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m');

            $webhost->waktu_chat_pertama = $waktu_chat_pertama;

            //loop cs_main_projects
            // Hitung profit_pembuatan (per project)
            $projects = $webhost->CsMainProjects;
            foreach ($projects as $project) {

                $dibayar = $project->dibayar;
                $tgl_masuk = $project->tgl_masuk ? Carbon::parse($project->tgl_masuk)->format('Y-m') : '';

                //jika tgl masuk sama dengan waktu chat pertama dan termasuk jenis pembuatan
                if ($tgl_masuk && $tgl_masuk == $waktu_chat_pertama && in_array($project->jenis, $this->jenis_pembuatan)) {
                    $total_profit_kotor_pembuatan += $dibayar;
                    $total_profit_bersih_pembuatan += $dibayar - $harga_domain;
                    $total_order_pembuatan++;
                }
            }

            // Kelompokkan hanya sekali per webhost
            $data_jenis = [];
            $grouped = $webhost->csMainProjects->groupBy('jenis');
            $total_profit_webhost = 0;
            foreach ($grouped as $jenis => $projects) {

                $total_dibayar = 0;
                $total_profit = 0;

                //loop projects
                foreach ($projects as $project) {
                    if (in_array($project->jenis, $this->jenis_pembuatan)) {
                        $total_profit += $project->dibayar - $harga_domain;
                    } else {
                        $total_profit += $project->dibayar;
                    }
                    $total_dibayar += $project->dibayar;
                }

                $data_jenis[$jenis]['label']    = $jenis;
                $data_jenis[$jenis]['dibayar']  = $total_dibayar;
                $data_jenis[$jenis]['profit']   = $total_profit;
                $data_jenis[$jenis]['total']    = $projects->count();

                $total_profit_webhost += $total_profit;
                $total_profit_kotor += $total_dibayar;

                //kelompokkan per jenis
                if (!isset($data_order_jenis[$jenis])) {
                    $data_order_jenis[$jenis] = [
                        'label'     => $jenis,
                        'dibayar'   => 0,
                        'total'     => 0,
                        'profit'    => 0
                    ];
                }
                $data_order_jenis[$jenis]['dibayar'] += $project->dibayar;
                $data_order_jenis[$jenis]['profit'] += $total_profit;
                $data_order_jenis[$jenis]['total'] += 1;
            }

            $webhost->data_jenis                        = $data_jenis;
            $webhost->total_profit                      = $total_profit_webhost;
            $total_profit_bersih                        += $total_profit_webhost;
        });

        $total_net_profit_pembuatan = $total_profit_bersih_pembuatan - $biaya_ads;

        $kumulatif = $this->kumulatif($bulan, $jangka_waktu);
        $net_profit_kumulatif = $kumulatif['net_profit'];

        return response()->json([
            'kumulatif'                 => $kumulatif,
            'data'                      => $data,
            'data_order_jenis'          => $data_order_jenis,
            'bulan'                     => $bulan_formatted,
            'info'                      => [
                'Total profit '                     => $total_profit_bersih,
                'Net Profit pembuatan'              => $total_net_profit_pembuatan,
                'Pertumbuhan profit selama ' . $jangka_waktu . 'tahun' => $total_profit_bersih - $total_net_profit_pembuatan,
            ],
            'info_pembuatan'                        => [
                'Biaya Ads '                        => 'Rp ' . number_format($biaya_ads, 0, ",", "."),
                'Harga Domain '                     => 'Rp ' . number_format($harga_domain, 0, ",", "."),
                'Order'                             => $total_order_pembuatan,
                'Profit Kotor'                      => 'Rp ' . number_format($total_profit_kotor_pembuatan, 0, ",", "."),
                'Profit Bersih'                     => 'Rp ' . number_format($total_profit_bersih_pembuatan, 0, ",", "."),
                'Net Profit'                        => 'Rp ' . number_format($total_net_profit_pembuatan, 0, ",", "."),
                'Net Profit Kumulatif'              => 'Rp ' . number_format($net_profit_kumulatif, 0, ",", "."),
                'Pertumbuhan Pembuatan Kumulatif '  => 'Rp ' . number_format($net_profit_kumulatif - $total_net_profit_pembuatan, 0, ",", "."),
            ]
        ]);
    }

    private function harga_domain($bulan)
    {
        //format bulan
        $formatter = new TanggalFormatterService();
        $bulan_formatted = $formatter->toIndonesianMonthYear($bulan);

        //get harga domain by bulan
        $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
        $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

        return $harga_domain;
    }

    public function kumulatif($bulan, $jangka_waktu)
    {

        $query = CsMainProject::with([
            'webhost'
        ]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($q) {
            $q->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram']);
        });

        // Filter csMainProjects.jenis
        $query->whereIn('jenis', $this->jenis_pembuatan);

        //filter waktu
        $dari = Carbon::parse($bulan)->startOfMonth()->format('Y-m-01 00:00:00');
        $sampai = Carbon::parse($bulan . ' + ' . $jangka_waktu . ' year')->endOfMonth()->format('Y-m-d 23:59:59');

        //filter csMainProjects.tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);


        $data = $query->get();

        $grouped_data = $data->groupBy(function ($item) {
            return Carbon::parse($item->webhost->waktu)->format('Y-m');
        });

        $data = $grouped_data[$bulan] ?? [];

        //hitung total
        $total_omzet = $data->sum('dibayar');
        $total_order = $data->count();
        $harga_domain = $this->harga_domain($bulan);
        $biaya_domain = $harga_domain * $total_order;
        $profit_kotor = $total_omzet - $biaya_domain;
        $biaya_ads      = BiayaAds::where('bulan', $bulan)->sum('biaya');
        $net_profit     = $profit_kotor - $biaya_ads;

        return [
            'dari'              => $dari,
            'sampai'            => $sampai,
            // 'result'            => $data,
            'total_omzet'       => $total_omzet,
            'total_order'       => $total_order,
            'biaya_domain'      => $biaya_domain,
            'profit_kotor'      => $profit_kotor,
            'biaya_ads'         => $biaya_ads,
            'net_profit'        => $net_profit
        ];
    }
}
