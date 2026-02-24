<?php

namespace App\Http\Controllers\Laporan;

use App\Helpers\TanggalFormatterHelper;
use App\Http\Controllers\Controller;
use App\Models\BiayaAds;
use App\Models\CsMainProject;
use App\Models\HargaDomain;
use App\Models\RekapChat;
use App\Services\ConvertDataLamaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NetProfitController extends Controller
{
    //
    public function index(Request $request)
    {

        $formatter = new TanggalFormatterHelper;

        $dari = $request->input('bulan_dari'); // format = YYYY-MMM
        // dapatkan hari pertama dari bulan $dari
        $dari = Carbon::parse($dari)->startOfMonth()->format('Y-m-d 00:00:00');
        $sampai = $request->input('bulan_sampai'); // format = YYYY-MMM
        // dapatkan hari terakhir dari bulan $sampai
        $sampai = Carbon::parse($sampai)->endOfMonth()->format('Y-m-d 23:59:59');

        $period = \Carbon\CarbonPeriod::create(
            Carbon::parse($dari)->startOfMonth(),
            '1 month',
            Carbon::parse($sampai)->startOfMonth()
        );

        $all_months = collect();

        foreach ($period as $date) {
            $key = $date->format('Ym');
            $all_months[$key] = $date->format('Y-m');
        }

        $campaign = $request->input('campaign');
        //set vias_filter_string
        if ($campaign == 'ads_k2') {
            $vias_filter = ['Whatsapp K2', 'Tidio Chat K2'];
            $kategori_biaya_ads = 'ads_k2';
        } else if ($campaign == 'ads_k3') {
            $vias_filter = ['Whatsapp K3', 'Tidio Chat K3'];
            $kategori_biaya_ads = 'ads_k3';
        } else if ($campaign == 'ads3') {
            $vias_filter = ['Whatsapp 3', 'Tidio Chat 3'];
            $kategori_biaya_ads = 'ads3';
        } else {
            $vias_filter = ['Whatsapp', 'Tidio Chat', 'Telegram'];
            $kategori_biaya_ads = 'ads';
        }

        // rekap chat
        $rekap_chat = $this->rekap_chat($dari, $sampai, $vias_filter);

        $jenis_pembuatan = [
            'Pembuatan',
            'Pembuatan apk',
            'Pembuatan apk custom',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting',
            'Pembuatan web konsep',
        ];

        // query
        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu',
            'webhost.paket:id_paket,paket',
        ]);

        // jenis = jenis_pembuatan
        $query->whereIn('jenis', $jenis_pembuatan);

        // filter by tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($query) use ($dari, $sampai, $vias_filter) {
            $query->whereIn('via', $vias_filter)
                ->whereBetween('waktu', [$dari, $sampai]);
        });

        // order by tgl_masuk
        $query->orderBy('tgl_masuk', 'desc');

        $raw_data = $query->get();

        // group by tgl_masuk: year and month
        $grouped = $raw_data->groupBy(function ($item) {
            return Carbon::parse($item->tgl_masuk)->format('Ym');
        });

        $final_data = collect();

        // hitung total biaya
        foreach ($all_months as $key => $the_bulan) {

            $items = $grouped[$key] ?? collect();

            $bulan_formatted = $formatter->toIndonesianMonthYear($the_bulan);

            $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
            $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

            $biaya_ads = BiayaAds::where('bulan', $the_bulan)
                ->where('kategori', $kategori_biaya_ads)
                ->sum('biaya') ?? 0;

            $omzet = 0;
            $total_order = 0;
            $projects = [];

            foreach ($items as $value) {

                $waktu_chat_pertama = Carbon::parse($value->webhost->waktu)->format('Y-m');
                $bln_masuk = Carbon::parse($value->tgl_masuk)->format('Y-m');

                if ($waktu_chat_pertama == $bln_masuk) {
                    $omzet += $value->dibayar;
                    $total_order++;
                    $projects[] = $value;
                }
            }

            $biaya_domain = $harga_domain * $total_order;
            $profit_kotor = $omzet - $biaya_domain;

            $chat_ads = $rekap_chat[$the_bulan]['total'] ?? 0;
            $chat_details = $rekap_chat[$the_bulan]['details'] ?? [];

            $persen_order = $chat_ads > 0
                ? round(($total_order / $chat_ads) * 100, 1) . '%'
                : '0%';

            $profit_kotor_order = $total_order > 0
                ? $profit_kotor / $total_order
                : 0;

            $net_profit = $profit_kotor - $biaya_ads;

            $biaya_per_order = $total_order > 0
                ? $biaya_ads / $total_order
                : 0;

            $biaya_per_chat = $chat_ads > 0
                ? $biaya_ads / $chat_ads
                : 0;

            $final_data->push([
                'bulan' => $the_bulan,
                'label' => $bulan_formatted,
                'omzet' => $omzet,
                'order' => $total_order,
                'biaya_iklan' => (int) $biaya_ads,
                'harga_domain' => $harga_domain,
                'biaya_domain' => $biaya_domain,
                'profit_kotor' => $profit_kotor,
                'projects' => $projects,
                'chat_ads' => $chat_ads,
                'chat_details' => $chat_details,
                'persen_order' => $persen_order,
                'profit_kotor_order' => $profit_kotor_order,
                'net_profit' => $net_profit,
                'biaya_per_order' => $biaya_per_order,
                'biaya_per_chat' => $biaya_per_chat,
            ]);
        }

        return response()->json([
            'dari' => $dari,
            'sampai' => $sampai,
            'campaign' => $campaign,
            'data' => $final_data->reverse()->values(),
            'rekap_chat' => $rekap_chat,
        ]);
    }

    private function rekap_chat($dari, $sampai, $vias_filter)
    {
        $query = RekapChat::whereBetween('chat_pertama', [$dari, $sampai])
            ->whereIn('via', $vias_filter)
            ->whereNotIn('alasan', ['Salah Sambung'])
            ->get();

        // group by month
        $query = $query->groupBy(function ($item) {
            return Carbon::parse($item->chat_pertama)->format('Y-m');
        });

        // get total
        $query = $query->map(function ($item) {
            return [
                'label' => Carbon::parse($item->first()->chat_pertama)->format('Y-m'),
                'total' => $item->count(),
                'details' => $item,
            ];
        });

        return $query;
    }

    public function perpanjangan(Request $request)
    {
        $formatter = new TanggalFormatterHelper;

        $dari = $request->query('bulan_dari'); // format = YYYY-MMM
        // dapatkan hari pertama dari bulan $dari
        $dari = Carbon::parse($dari)->startOfMonth()->format('Y-m-d 00:00:00');
        $sampai = $request->query('bulan_sampai'); // format = YYYY-MMM
        // dapatkan hari terakhir dari bulan $sampai
        $sampai = Carbon::parse($sampai)->endOfMonth()->format('Y-m-d 23:59:59');

        // query
        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu',
            'webhost.paket:id_paket,paket',
        ]);

        // jenis = jenis_pembuatan
        $query->where('jenis', 'Perpanjangan');

        // filter by tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);

        // filter relasi webhost via
        // $query->whereHas('webhost', function ($query) use ($dari, $sampai) {
        //     $query->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram'])
        //         ->whereBetween('waktu', [$dari, $sampai]);
        // });

        // order by tgl_masuk
        $query->orderBy('tgl_masuk', 'desc');

        $raw_data = $query->get();

        // group by tgl_masuk: year and month
        $raw_data = $raw_data->groupBy(function ($item) {
            return Carbon::parse($item->tgl_masuk)->format('Ym');
        });

        $raw_data = $raw_data->map(function ($item) use ($formatter) {

            $the_bulan = Carbon::parse($item->first()->tgl_masuk)->format('Y-m');

            // format bulan
            $bulan_formatted = $formatter->toIndonesianMonthYear($the_bulan);

            // get harga domain by bulan
            $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
            $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

            $omzet = 0;
            foreach ($item as $value) {
                $omzet += $value->dibayar;
            }
            return [
                'label' => $formatter->toIndonesianMonthYear($item->first()->tgl_masuk),
                'total' => count($item),
                'omzet' => $omzet,
                'harga_domain' => $harga_domain,
                'biaya_domain' => $harga_domain * count($item),
                'profit' => $omzet - ($harga_domain * count($item)),
            ];
        });

        // array remove key
        $raw_data = $raw_data->values()->toArray();

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'data' => $raw_data,
        ];
    }
}
