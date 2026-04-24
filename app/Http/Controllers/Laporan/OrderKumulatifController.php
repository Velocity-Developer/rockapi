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
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class OrderKumulatifController extends Controller
{

    private  $jenis_pembuatan = [
        'Pembuatan',
        'Pembuatan apk',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
        'Pembuatan web konsep',
    ];

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

        $period = CarbonPeriod::create(
            Carbon::parse($dari)->startOfMonth(),
            '1 month',
            Carbon::parse($sampai)->startOfMonth()
        );

        $all_months = collect();
        foreach ($period as $date) {
            $all_months[$date->format('Y-m')] = $date->copy();
        }

        $campaign = $request->input('campaign');
        //set vias_filter_string
        if ($campaign == 'ads_k2') {
            $vias_filter = ['Whatsapp K2', 'Tidio Chat K2'];
            $kategori_biaya_ads = 'ads_k2';
        } else if ($campaign == 'ads_k3') {
            $vias_filter = ['Whatsapp K3', 'Tidio Chat K3'];
            $kategori_biaya_ads = 'ads_k3';
        } else {
            $vias_filter = ['Whatsapp', 'Tidio Chat', 'Telegram'];
            $kategori_biaya_ads = 'ads';
        }

        // rekap chat
        $rekap_chat = $this->rekap_chat($dari, $sampai, $vias_filter);

        // query
        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu,wa',
            'webhost.paket:id_paket,paket',
        ])
            ->whereIn('jenis', $this->jenis_pembuatan)
            ->whereBetween('tgl_masuk', [$dari, $sampai])
            ->orderBy('tgl_masuk', 'desc');

        // template lama hanya memfilter via dari relasi webhost,
        // lalu rekap per bulan mengikuti bulan chat pertama (webhost.waktu).
        $query->whereHas('webhost', function ($query) use ($vias_filter) {
            $query->whereIn('via', $vias_filter);
        });
        $raw_data = $query->get()->filter(function ($item) {
            $waktu = $item->webhost->waktu ?? null;

            return ! empty($waktu) && $waktu !== '0000-00-00 00:00:00';
        });

        // template lama mengelompokkan order berdasarkan bulan chat pertama.
        $grouped_projects = $raw_data->groupBy(function ($item) {
            return Carbon::parse($item->webhost->waktu)->format('Y-m');
        });

        $needs_ads_conversion = false;
        $final_data = collect();

        foreach ($all_months->reverse() as $the_bulan => $month_date) {
            $items = $grouped_projects->get($the_bulan, collect());
            $bulan_formatted = $formatter->toIndonesianMonthYear($the_bulan);

            $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
            $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 157000;

            $biaya_ads = BiayaAds::where('bulan', $the_bulan)->where('kategori', $kategori_biaya_ads)->sum('biaya');
            if (! $biaya_ads) {
                $needs_ads_conversion = true;
            }

            // template lama menghitung omzet terpisah dengan filter biaya > 150000.
            $omzet = (int) $items
                ->filter(fn ($item) => (int) ($item->biaya ?? 0) > 150000)
                ->sum('dibayar');

            $total_order = $items->count();
            $projects = $items->values();
            $biaya_domain = $harga_domain * $total_order;
            $profit_kotor = $omzet - $biaya_domain;
            $chat_ads = $rekap_chat[$the_bulan]['total'] ?? 0;
            $chat_details = $rekap_chat[$the_bulan]['details'] ?? collect();
            $persen_order = $chat_ads > 0
                ? round(($total_order / $chat_ads) * 100, 1) . '%'
                : '0%';
            $profit_kotor_order = $total_order > 0 ? $profit_kotor / $total_order : 0;
            $net_profit = $profit_kotor - $biaya_ads;
            $biaya_per_order = $total_order > 0 ? $biaya_ads / $total_order : 0;
            $biaya_per_chat = $chat_ads > 0 ? $biaya_ads / $chat_ads : 0;

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

        if ($needs_ads_conversion) {
            try {
                (new ConvertDataLamaService)->handle_biaya_ads();

                $final_data = $final_data->map(function ($item) use ($kategori_biaya_ads) {
                    if (! empty($item['biaya_iklan'])) {
                        return $item;
                    }

                    $biaya_ads = (int) BiayaAds::where('bulan', $item['bulan'])
                        ->where('kategori', $kategori_biaya_ads)
                        ->sum('biaya');

                    $item['biaya_iklan'] = $biaya_ads;
                    $item['net_profit'] = $item['profit_kotor'] - $biaya_ads;
                    $item['biaya_per_order'] = $item['order'] > 0
                        ? $biaya_ads / $item['order']
                        : 0;
                    $item['biaya_per_chat'] = $item['chat_ads'] > 0
                        ? $biaya_ads / $item['chat_ads']
                        : 0;

                    return $item;
                });
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return response()->json([
            'dari' => $dari,
            'sampai' => $sampai,
            'campaign' => $campaign,
            'data' => $final_data->values(),
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
}
