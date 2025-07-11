<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\TanggalFormatterService;

use App\Models\CsMainProject;
use App\Models\HargaDomain;
use App\Models\BiayaAds;

class NetProfitController extends Controller
{
    //
    public function index(Request $request)
    {

        $formatter = new TanggalFormatterService();

        $dari = $request->input('bulan_dari'); //format = YYYY-MMM
        //dapatkan hari pertama dari bulan $dari
        $dari = Carbon::parse($dari)->startOfMonth()->format('Y-m-d 00:00:00');
        $sampai = $request->input('bulan_sampai'); //format = YYYY-MMM
        //dapatkan hari terakhir dari bulan $sampai
        $sampai = Carbon::parse($sampai)->endOfMonth()->format('Y-m-d 23:59:59');

        $jenis_pembuatan = [
            'Pembuatan',
            'Pembuatan apk',
            'Pembuatan apk custom',
            'Pembuatan Tanpa Domain',
            'Pembuatan Tanpa Hosting',
            'Pembuatan Tanpa Domain+Hosting'
        ];

        //query
        $query = CsMainProject::with([
            'webhost:id_webhost,nama_web,id_paket,via,waktu',
            'webhost.paket:id_paket,paket',
        ]);

        //jenis = jenis_pembuatan
        $query->whereIn('jenis', $jenis_pembuatan);

        //filter by tgl_masuk
        $query->whereBetween('tgl_masuk', [$dari, $sampai]);

        // filter relasi webhost via
        $query->whereHas('webhost', function ($query) use ($dari, $sampai) {
            $query->whereIn('via', ['Whatsapp', 'Tidio Chat', 'Telegram'])
                ->whereBetween('waktu', [$dari, $sampai]);
        });

        //order by tgl_masuk
        $query->orderBy('tgl_masuk', 'desc');

        $raw_data = $query->get();

        //group by tgl_masuk: year and month
        $raw_data = $raw_data->groupBy(function ($item) {
            return Carbon::parse($item->tgl_masuk)->format('Ym');
        });

        //hitung total biaya
        $raw_data = $raw_data->map(function ($item) use ($formatter) {

            $the_bulan = Carbon::parse($item->first()->tgl_masuk)->format('Y-m');

            //format bulan
            $bulan_formatted = $formatter->toIndonesianMonthYear($the_bulan);

            //get harga domain by bulan
            $harga_domain = HargaDomain::where('bulan', $bulan_formatted)->first();
            $harga_domain = $harga_domain ? $harga_domain->biaya_normalized : 0;

            //get total biaya ads by bulan
            $biaya_ads = BiayaAds::where('bulan', $the_bulan)->sum('biaya');

            $omzet = 0;
            $total_order = 0;
            $projects = [];

            foreach ($item as $value) {

                //waktu chat pertama
                $waktu_chat_pertama = $value->webhost->waktu;
                //ubah format
                $waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m');
                //sisipkan ke dalam item
                $value->waktu_chat_pertama = Carbon::parse($waktu_chat_pertama)->format('Y-m-d');

                //ubah format tgl_masuk
                $bln_masuk = Carbon::parse($value->tgl_masuk)->format('Y-m');

                //jika waktu_chat_pertama = tgl_masuk
                if ($waktu_chat_pertama == $bln_masuk) {
                    $omzet += $value->dibayar;
                    $total_order += 1;
                    $projects[] = $value;
                }
            }

            // $total_project = $item->count();
            $total_project = $total_order;
            $biaya_domain = $harga_domain * $total_project;
            $profit_kotor = $omzet - $biaya_domain;

            return [
                'label'         => $formatter->toIndonesianMonthYear($item->first()->tgl_masuk),
                'omzet'         => $omzet,
                'order'         => $total_project,
                'biaya_iklan'   => $biaya_ads,
                'harga_domain'  => $harga_domain,
                'biaya_domain'  => $biaya_domain,
                'profit_kotor'  => $profit_kotor,
                'projects'      => $projects
            ];
        });

        //array remove key
        $raw_data = $raw_data->toArray();

        return response()->json([
            'dari'          => $dari,
            'sampai'        => $sampai,
            'data'          => $raw_data
        ]);
    }
}
