<?php

namespace App\Http\Controllers\Laporan;

use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\TanggalFormatterHelper;
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
        $bulan_start  = $request->input('bulan_start');
        $bulan_end    = $request->input('bulan_end');

        //hitung jangka waktu dari bulan_start sampai bulan_end
        $jangka_waktu = Carbon::parse($bulan_start)->diffInMonths(Carbon::parse($bulan_end));
        $jangka_waktu_tahun = floor($jangka_waktu / 12);

        //format bulan
        $formatter = new TanggalFormatterHelper();
        $bulan_formatted = $formatter->toIndonesianMonthYear($bulan_start);

        //get harga domain by bulan
        $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
        $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

        //get total biaya ads by bulan
        $biaya_ads = BiayaAds::where('bulan', $bulan_start)->sum('biaya');

        //jika kosong, maka jalankan fungsi service ConvertDataLamaService::handle_biaya_ads
        if (!$biaya_ads) {
            try {
                (new ConvertDataLamaService())->handle_biaya_ads();
                $biaya_ads = BiayaAds::where('bulan', $bulan_start)->sum('biaya');
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu',
            'webhost.paket:id_paket,paket',
            'webhost.csMainProjects' => function ($q) use ($bulan_start, $bulan_end, $request) {
                if ($bulan_start && $bulan_end) {
                    $start = Carbon::create($bulan_start)->startOfMonth();
                    $end = Carbon::create($bulan_end)->endOfMonth();

                    $q->whereBetween('tgl_masuk', [$start, $end])
                        ->orderBy('tgl_masuk');
                }
            }
        ]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($q) use ($bulan_start) {
            $q->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram']);
            //waktu chat pertama = $bulan
            $q->where('waktu', 'like', '%' . $bulan_start . '%');
        });

        // filter jenis pada cs_main_project
        $query->whereIn('jenis', $this->jenis_pembuatan);

        if ($bulan_start) {
            [$year, $month] = explode('-', $bulan_start);
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
        $data_order_jenis_total = 0;

        $data->each(function ($project) use (
            &$total_profit_kotor,
            &$total_profit_bersih,
            &$harga_domain,
            &$total_profit_kotor_pembuatan,
            &$total_profit_bersih_pembuatan,
            &$total_order_pembuatan,
            &$data_order_jenis,
            &$data_order_jenis_total
        ) {

            $webhost = $project->webhost;

            //waktu chat pertama
            $waktu_chat_pertama = $webhost->waktu;
            $waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m');

            $webhost->waktu_chat_pertama = $waktu_chat_pertama;

            // loop cs_main_projects
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
                if (!in_array($jenis, $this->jenis_pembuatan)) {
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
                    $data_order_jenis_total += $total_profit;
                }
            }

            $webhost->data_jenis                        = $data_jenis;
            $webhost->total_profit                      = $total_profit_webhost;
            $total_profit_bersih                        += $total_profit_webhost;
        });

        $kumulatif              = $this->kumulatif($bulan_start, $bulan_end);
        $order_kumulatif        = $kumulatif['total_order'];
        $net_profit_kumulatif   = $kumulatif['net_profit'];

        /**
         * perubahan dari MUH, ambil dari kumulatif
         * replace total_profit_bersih dengan total_profit_bersih dari kumulatif
         */
        $total_profit_bersih = $kumulatif['total_profit_bersih'];
        /**
         * end ambil dari kumulatif
         */

        $total_net_profit_pembuatan = $total_profit_bersih_pembuatan - $biaya_ads;
        $total_net_profit           = $total_profit_bersih - $biaya_ads;
        $pertumbuhan                = ($total_net_profit - $total_net_profit_pembuatan);
        $persen_pertumbuhan         = ($total_net_profit - $total_net_profit_pembuatan) / $total_net_profit_pembuatan * 100;
        $persen_pertumbuhan         = bcdiv($persen_pertumbuhan, '1', 2);

        /**
         * perubahan dari MUH, ambil dari kumulatif
         * replace data_order_jenis dari kumulatif
         */
        $data_order_jenis       = $kumulatif['data_order_jenis'];
        $data_order_jenis_total = $kumulatif['data_order_jenis_total'];
        /**
         * end ambil dari kumulatif
         */

        $data_order_jenis['Total'] = [
            'label'     => 'Total',
            'dibayar'   => '',
            'total'     => collect($data_order_jenis)->sum('total'),
            'profit'    => $data_order_jenis_total
        ];

        return response()->json([
            'kumulatif'                 => $kumulatif,
            'data'                      => $data,
            'data_order_jenis'          => $data_order_jenis,
            'data_order_jenis_total'    => $data_order_jenis_total,
            'bulan'                     => $bulan_formatted,
            'info'                      => [
                'Total profit '             => 'Rp ' . number_format($total_profit_bersih, 0, ",", "."),
                'Total Net Profit '         => 'Rp ' . number_format($total_net_profit, 0, ",", "."),
                'Pertumbuhan profit selama ' . $jangka_waktu_tahun . ' tahun' => 'Rp ' . number_format($pertumbuhan, 0, ",", ".") . ' (' . $persen_pertumbuhan . '%)',
            ],
            'info_pembuatan'                        => [
                'Biaya Ads '                        => 'Rp ' . number_format($biaya_ads, 0, ",", "."),
                'Harga Domain '                     => 'Rp ' . number_format($harga_domain, 0, ",", "."),
                'Order'                             => $total_order_pembuatan,
                'Net Profit'                        => 'Rp ' . number_format($total_net_profit_pembuatan, 0, ",", "."),
                'Order Kumulatif'                   => $order_kumulatif,
                'Net Profit Kumulatif'              => 'Rp ' . number_format($net_profit_kumulatif, 0, ",", "."),
                'Pertumbuhan Pembuatan Kumulatif'   => 'Rp ' . number_format($net_profit_kumulatif - $total_net_profit_pembuatan, 0, ",", "."),
            ]
        ]);
    }

    private function harga_domain($bulan)
    {
        //format bulan
        $formatter          = new TanggalFormatterHelper();
        $bulan_formatted    = $formatter->toIndonesianMonthYear($bulan);

        //get harga domain by bulan
        $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
        $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

        return $harga_domain;
    }

    public function kumulatif($bulan_start, $bulan_end)
    {

        $query = CsMainProject::with([
            'webhost',
            'webhost.csMainProjects' => function ($q) use ($bulan_start, $bulan_end) {
                if ($bulan_start && $bulan_end) {
                    $start = Carbon::create($bulan_start)->startOfMonth();
                    $end = Carbon::create($bulan_end)->endOfMonth();

                    $q->whereBetween('tgl_masuk', [$start, $end])
                        ->orderBy('tgl_masuk');
                }
            }
        ]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($q) {
            $q->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram']);
        });

        // Filter csMainProjects.jenis
        $query->whereIn('jenis', $this->jenis_pembuatan);

        //filter waktu
        $dari = Carbon::parse($bulan_start)->startOfMonth()->format('Y-m-01 00:00:00');
        $sampai = Carbon::parse($bulan_end)->endOfMonth()->format('Y-m-d 23:59:59');

        //filter csMainProjects.tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);


        $data = $query->get();

        $grouped_data = $data->groupBy(function ($item) {
            return Carbon::parse($item->webhost->waktu)->format('Y-m');
        });

        //ambil data berdasarkan bulan
        $data = $grouped_data[$bulan_start] ?? [];

        //hitung total
        $total_omzet    = $data ? $data->sum('dibayar') : 0;
        $total_order    = $data ? $data->count() : 0;
        $harga_domain   = $this->harga_domain($bulan_start);
        $biaya_domain   = $harga_domain * $total_order;
        $profit_kotor   = $total_omzet - $biaya_domain;
        $biaya_ads      = BiayaAds::where('bulan', $bulan_start)->sum('biaya');
        $net_profit     = $profit_kotor - $biaya_ads;

        $total_order_pembuatan = 0;
        $total_profit_kotor_pembuatan = 0;
        $total_profit_bersih_pembuatan = 0;
        $total_profit_bersih    = 0;
        $data_order_jenis       = [];
        $data_order_jenis_total = 0;

        if ($data) {
            $data->each(function ($project) use (
                &$total_order_pembuatan,
                &$total_profit_kotor_pembuatan,
                &$total_profit_bersih_pembuatan,
                &$harga_domain,
                &$total_profit_bersih,
                &$data_order_jenis,
                &$data_order_jenis_total
            ) {
                $webhost = $project->webhost;

                //waktu chat pertama
                $waktu_chat_pertama = $webhost->waktu;
                $waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m');

                $webhost->waktu_chat_pertama = $waktu_chat_pertama;

                // loop cs_main_projects
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
                $data_jenis             = [];
                $grouped                = $webhost->csMainProjects->groupBy('jenis');
                $total_profit_webhost   = 0;
                foreach ($grouped as $jenis => $projects) {

                    $total_dibayar  = 0;
                    $total_profit   = 0;
                    $harga_domain   = $this->harga_domain($project->webhost->waktu);

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

                    //kelompokkan per jenis
                    if (!in_array($jenis, $this->jenis_pembuatan)) {
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
                        $data_order_jenis_total += $total_profit;
                    }
                }

                $webhost->data_jenis                        = $data_jenis;
                $webhost->total_profit                      = $total_profit_webhost;

                $total_profit_bersih += $total_profit_webhost;
            });
        }

        return [
            'dari'                  => $dari,
            'sampai'                => $sampai,
            'data'                  => $data,
            'total_omzet'           => $total_omzet,
            'total_order'           => $total_order,
            'biaya_domain'          => $biaya_domain,
            'profit_kotor'          => $profit_kotor,
            'biaya_ads'             => $biaya_ads,
            'net_profit'            => $net_profit,
            'total_profit_bersih'           => $total_profit_bersih,
            'data_order_jenis'              => $data_order_jenis,
            'data_order_jenis_total'        => $data_order_jenis_total,
            'total_profit_bersih_pembuatan' => $total_profit_bersih_pembuatan
        ];
    }
}
