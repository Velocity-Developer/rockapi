<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\TanggalFormatterService;

use App\Models\CsMainProject;
use App\Models\HargaDomain;
use App\Models\BiayaAds;

class PerpanjangWebJangkaController extends Controller
{
    //
    public function index(Request $request)
    {
        $data = [];
        $jangka_waktu = (int) $request->input('jangka') ?? 1;
        $bulan = $request->input('bulan');

        //format bulan
        $formatter = new TanggalFormatterService();
        $bulan_formatted = $formatter->toIndonesianMonthYear($bulan);

        //get harga domain by bulan
        $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
        $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

        //get total biaya ads by bulan
        $biaya_ads = BiayaAds::where('bulan', $bulan)->sum('biaya');

        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket',
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

        // Filter utama
        $query->where('jenis', 'like', '%Pembuatan%');

        if ($request->filled('bulan')) {
            [$year, $month] = explode('-', $request->input('bulan'));
            $query->whereYear('tgl_masuk', $year)
                ->whereMonth('tgl_masuk', $month);
        }

        $data = $query->get();

        $total_profit_kotor = 0;
        $total_profit_bersih = 0;
        $total_profit_kotor_pembuatan = 0;

        $data->each(function ($project) use (
            &$total_profit_kotor,
            &$total_profit_bersih,
            &$harga_domain,
            &$total_profit_kotor_pembuatan
        ) {
            $webhost = $project->webhost;

            if ($webhost && $webhost->relationLoaded('csMainProjects')) {
                $grouped = $webhost->csMainProjects->groupBy('jenis');

                $rekap = [];
                $profit_web = 0;

                foreach ($grouped as $jenis => $projects) {
                    $biaya_sum  = $projects->sum('biaya');
                    $profit     = $biaya_sum;

                    //logic profit
                    //jika jenis = 'Pembuatan Web'
                    if ($jenis == 'Pembuatan Web' || $jenis == 'Pembuatan' || $jenis == 'Pembuatan Tanpa Domain') {
                        $profit = $biaya_sum - $harga_domain;
                        $total_profit_kotor_pembuatan += $biaya_sum;
                    }

                    $total_profit_kotor     += $biaya_sum;
                    $total_profit_bersih    += $profit;
                    $profit_web             += $profit;

                    $rekap['jenis'][$jenis]['total'] = $projects->count();
                    $rekap['jenis'][$jenis]['biaya'] = $biaya_sum;
                    $rekap['jenis'][$jenis]['profit'] = $profit;
                }

                //total profit
                $rekap['total'] = $profit_web;

                // Tambahkan hasil ke properti virtual
                $webhost->rekap_biaya = $rekap;
            }
        });

        $total_net_profit = $total_profit_kotor - $biaya_ads;

        return response()->json([
            'data'                      => $data,
            'bulan'                     => $bulan_formatted,
            'info'                      => [
                'Harga Domain ' . $bulan_formatted  => $harga_domain,
                'Biaya Ads ' . $bulan_formatted     => $biaya_ads,
                'Profit Kotor Pembuatan'            => $total_profit_kotor_pembuatan,
                'Net Profit Pembuatan'              => $total_net_profit,
                'Profit Kotor'                      => $total_profit_kotor,
                'Profit Bersih'                     => $total_profit_bersih,
                'Pertumbuhan Profit ' . $jangka_waktu . ' tahun'    => ($total_net_profit - $total_profit_kotor_pembuatan),
            ],
        ]);
    }
}
