<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\CsMainProject;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function welcome()
    {
        //dapatkan total CsMainProject bulan ini
        $totalCsMainProject = CsMainProject::whereMonth('tgl_masuk', date('m'))->count();

        //tanggal sekarang
        $tanggalSekarang = date('d');

        //dapatkan total CsMainProject bulan lalu, tapi sampai tanggal sekarang
        $totalCsMainProjectLastMonth = CsMainProject::whereMonth('tgl_masuk', date('m', strtotime('-1 month')))
            ->whereDay('tgl_masuk', '<=', $tanggalSekarang)
            ->count();


        //hitung persentase
        $persentase = ($totalCsMainProject - $totalCsMainProjectLastMonth) / $totalCsMainProjectLastMonth * 100;

        return response()->json([
            'total_project_bulanini' => $totalCsMainProject,
            'total_project_bulanlalu' => $totalCsMainProjectLastMonth,
            'perform' => round($persentase, 2),
        ]);
    }

    public function chart_bulanini()
    {
        //dapatkan array data perhari CsMainProject bulan ini
        $data = [];
        $labels = [];
        for ($i = 1; $i <= date('t'); $i++) {
            $count = CsMainProject::whereMonth('tgl_masuk', date('m'))
                ->whereDay('tgl_masuk', $i)
                ->count();

            $data[] = $count;
            $labels[] = date('d', strtotime(date('Y-m-') . $i));
        }

        return response()->json([
            'data' => $data,
            'labels' => $labels,
        ]);
    }

    public function chart_tahunini()
    {
        //array bulan
        $bulan = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        //dapatkan array data perbulan CsMainProject tahun ini,
        //dikelompokkan dari kolom jenis
        $gets = CsMainProject::whereYear('tgl_masuk', Carbon::now()->year)
            ->groupBy('month', 'jenis')
            ->selectRaw('MONTH(tgl_masuk) as month, jenis, COUNT(*) as jenis_count')
            ->orderBy('month')
            ->get();
        $get_collection = collect($gets);

        $jenises = $gets->unique('jenis')->map(function ($gets) {
            return $gets->jenis;
        })->toArray();


        $datasets = [];
        foreach ($jenises as $i => $jn) {

            $datah = [];
            foreach ($bulan as $b => $bln) {
                $jenisCount = $get_collection->where('jenis', $jn)->where('month', ($b + 1))->first()['jenis_count'] ?? 0;
                $datah[] = $jenisCount;
            }

            //default hidden
            $skip = [
                'Jasa buat google maps',
                'Lain-lain',
                'Lain - Lain',
                'Jasa Buat Facebook',
                'Jasa Buat Akun Sosmed',
                'Jasa Buat Email',
                'Jasa Pembuatan Logo',
                'Compro PDF',
                'Jasa Input Produk',
                'Jasa rating google maps'
            ];
            $hidden = in_array($jn, $skip) ? true : false;

            $datasets[] = [
                'pointRadius'   => 1,
                'tension'       => 0.4,
                'label'         => $jn,
                'data'          => $datah,
                'hidden'        => $hidden,
            ];
        }

        return response()->json([
            // 'raw' => $gets,
            // 'data' => $datas,
            'labels' => $bulan,
            'datasets' => $datasets,
        ]);
    }

    public function chart_hariini()
    {
        //dapatkan CsMainProject hari ini
        $hariini = date('Y-m-d');

        $gets = CsMainProject::where('tgl_masuk', $hariini)
            ->groupBy('jenis')
            ->selectRaw('jenis, COUNT(*) as jenis_count')
            ->orderBy('jenis_count', 'desc')->get();

        if ($gets) {
            $get_collection = collect($gets);
            $jenises = $gets->unique('jenis')->map(function ($gets) {
                return $gets->jenis;
            })->toArray();

            $data = [];
            foreach ($jenises as $i => $jn) {
                $data[] = $get_collection->where('jenis', $jn)->first()['jenis_count'];
            }
        } else {
            $jenises = ['Tidak ada project'];
            $data = [100];
        }

        return response()->json([
            'labels' => $jenises,
            'data' => $data,
            'raw'   => $gets,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function datatable()
    {
        $Posts = Post::with('author:id,name,avatar')
            ->orderBy('date', 'desc')
            ->paginate(8);
        return response()->json($Posts);
    }
}
