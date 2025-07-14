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
    //
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
        $query->whereIn('jenis', [
            'Pembuatan',
            'Pembuatan apk',
            'Pembuatan apk custom',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting'
        ]);

        // Filter utama
        // $query->where('jenis', 'like', '%Pembuatan%');

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

        $data->each(function ($project) use (
            &$total_profit_kotor,
            &$total_profit_bersih,
            &$harga_domain,
            &$total_profit_kotor_pembuatan,
            &$total_profit_bersih_pembuatan,
            &$total_order_pembuatan
        ) {

            $webhost = $project->webhost;

            //waktu chat pertama
            $waktu_chat_pertama = $webhost->waktu;
            $waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m');

            $webhost->waktu_chat_pertama = $waktu_chat_pertama;

            //loop cs_main_projects
            $projects = $webhost->CsMainProjects;
            foreach ($projects as $project) {

                $jenis_pembuatan = [
                    'Pembuatan',
                    'Pembuatan apk',
                    'Pembuatan apk custom',
                    'Pembuatan Tanpa Domain',
                    'Pembuatan Tanpa Hosting',
                    'Pembuatan Tanpa Domain+Hosting'
                ];

                $dibayar = $project->dibayar;
                $tgl_masuk = $project->tgl_masuk ? Carbon::parse($project->tgl_masuk)->format('Y-m') : '';

                //jika tgl masuk sama dengan waktu chat pertama dan termasuk jenis pembuatan
                if ($tgl_masuk && $tgl_masuk == $waktu_chat_pertama && in_array($project->jenis, $jenis_pembuatan)) {
                    $total_profit_kotor_pembuatan += $dibayar;
                    $total_profit_bersih_pembuatan += $dibayar - $harga_domain;
                    $total_order_pembuatan++;
                }

                $total_profit_bersih += $dibayar - $harga_domain;
                $total_profit_kotor += $dibayar;
            }

            //     if ($webhost && $webhost->relationLoaded('csMainProjects')) {



            //         // Ambil project pertama dari webhost
            //         $firstProject = $webhost->csMainProjects->sortBy('tgl_masuk')->first();
            //         $firstJenis = $firstProject?->jenis;

            //         // Skip perhitungan jika project pertama bukan termasuk jenis pembuatan
            //         if (!$firstProject || !in_array($firstProject->jenis, $jenis_pembuatan)) {
            //             return; // keluar dari each
            //         }

            //         $grouped = $webhost->csMainProjects->groupBy('jenis');


            //         $rekap = [];
            //         $profit_web = 0;
            //         $urutan = 1;

            //         foreach ($grouped as $jenis => $projects) {

            //             $biaya_sum  = $projects->sum('dibayar');
            //             // $biaya_sum  = 0;
            //             $profit     = $biaya_sum;

            //             //hitung profit
            //             foreach ($projects as $i => $project) {
            //                 //jika jenis in array jenis_pembuatan
            //                 if (in_array($project->jenis, $jenis_pembuatan) && $i == 0) {
            //                     $profit = $biaya_sum - $harga_domain;
            //                     // $total_profit_kotor_pembuatan += $project->dibayar;
            //                     $total_profit_kotor_pembuatan += 1;
            //                 } else {
            //                     $profit = $biaya_sum - $harga_domain;
            //                 }
            //             }

            //             $total_profit_kotor     += $biaya_sum;
            //             $total_profit_bersih    += $profit;
            //             $profit_web             += $profit;

            //             $rekap['jenis'][$jenis]['label'] = $urutan . '.' . $jenis;
            //             $rekap['jenis'][$jenis]['total'] = $projects->count();
            //             $rekap['jenis'][$jenis]['biaya'] = $biaya_sum;
            //             $rekap['jenis'][$jenis]['profit'] = $profit;

            //             $urutan++;
            //         }

            //         //total profit
            //         $rekap['total'] = $profit_web;

            //         // Tambahkan hasil ke properti virtual
            //         $webhost->rekap_biaya = $rekap;
            //     }
        });

        $total_net_profit_pembuatan = $total_profit_bersih_pembuatan - $biaya_ads;


        return response()->json([
            'kumulatif'                 => $this->kumulatif($bulan, $jangka_waktu),
            'data'                      => $data,
            'bulan'                     => $bulan_formatted,
            'info'                      => [
                'Harga Domain ' . $bulan_formatted  => $harga_domain,
                'Biaya Ads ' . $bulan_formatted     => $biaya_ads,
                'Profit Kotor Pembuatan'            => $total_profit_kotor_pembuatan,
                'Profit Bersih Pembuatan'            => $total_profit_bersih_pembuatan,
                'Net Profit Pembuatan'              => $total_net_profit_pembuatan,
                'Profit Kotor'                      => $total_profit_kotor,
                'Profit Bersih'                     => $total_profit_bersih,
                'Pertumbuhan Profit ' . $jangka_waktu . ' tahun'    => ($total_profit_kotor_pembuatan - $total_profit_kotor_pembuatan),
            ],
            'info_pembuatan'                        => [
                'Biaya Ads '                        => 'Rp ' . number_format($biaya_ads, 0, ",", "."),
                'Harga Domain '                     => 'Rp ' . number_format($harga_domain, 0, ",", "."),
                'Order'                             => $total_order_pembuatan,
                'Profit Kotor'                      => 'Rp ' . number_format($total_profit_kotor_pembuatan, 0, ",", "."),
                'Profit Bersih'                     => 'Rp ' . number_format($total_profit_bersih_pembuatan, 0, ",", "."),
                'Net Profit'                        => 'Rp ' . number_format($total_net_profit_pembuatan, 0, ",", "."),
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
        $query->whereIn('jenis', [
            'Pembuatan',
            'Pembuatan apk',
            'Pembuatan apk custom',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting'
        ]);

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
            'result'            => $data,
            'total_omzet'       => $total_omzet,
            'total_order'       => $total_order,
            'biaya_domain'      => $biaya_domain,
            'profit_kotor'      => $profit_kotor,
            'biaya_ads'         => $biaya_ads,
            'net_profit'        => $net_profit
        ];
    }
}
