<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CsMainProject;
use Carbon\Carbon;

class DashboardWebdeveloperController extends Controller
{

    private $jenis_project_webdeveloper = [
        'Jasa Update Web',
        'Pembuatan',
        'Pembuatan apk custom',
        'Pembuatan Tanpa Domain',
        'Pembuatan Tanpa Hosting',
        'Pembuatan Tanpa Domain+Hosting',
        'Redesign',
        'Pembuatan web konsep',
    ];

    public function welcome()
    {
        //dapatkan total CsMainProject bulan ini
        $totalCsMainProject = CsMainProject::whereMonth('tgl_masuk', date('m'))
            ->whereYear('tgl_masuk', date('Y'))
            ->whereIn('jenis', $this->jenis_project_webdeveloper)
            ->count();

        //tanggal sekarang
        $now = Carbon::now();
        $tanggalSekarang = $now->day;

        // range bulan lalu
        $start = $now->copy()->subMonth()->startOfMonth();
        $end   = $now->copy()->subMonth()->startOfMonth()->addDays($tanggalSekarang - 1);

        $totalCsMainProjectLastMonth = CsMainProject::whereBetween('tgl_masuk', [$start, $end])
            ->whereIn('jenis', $this->jenis_project_webdeveloper)
            ->count();

        //hitung persentase
        $persentase = ($totalCsMainProject - $totalCsMainProjectLastMonth) / $totalCsMainProjectLastMonth * 100;

        return response()->json([
            'total_project_bulanini' => $totalCsMainProject,
            'total_project_bulanlalu' => $totalCsMainProjectLastMonth,
            'perform' => round($persentase, 2),
            'date' => date('m'),
        ]);
    }

    public function chart_project_tahun_terakhir_webdeveloper()
    {
        // Array nama bulan dalam bahasa Indonesia
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
            'Desember'
        ];

        // Dapatkan data 12 bulan terakhir dari bulan ini
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(11)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        // Query data CsMainProject untuk 12 bulan terakhir dengan jenis webdeveloper
        $projects = CsMainProject::whereBetween('tgl_masuk', [$startDate, $endDate])
            ->whereIn('jenis', $this->jenis_project_webdeveloper)
            ->selectRaw("
        DATE_FORMAT(tgl_masuk, '%m-%Y') as bulan_tahun,
        dikerjakan_oleh,
        COUNT(*) as total
    ")
            ->groupBy('bulan_tahun', 'dikerjakan_oleh')
            ->orderByRaw("MIN(tgl_masuk)")
            ->get();

        $projects = $projects->groupBy('bulan_tahun')->map(function ($items) {
            return $items->mapWithKeys(function ($item) {
                $oleh = str_replace(',', '', $item->dikerjakan_oleh);
                $oleh = str_replace('[100]', '', $oleh);
                return [$oleh => $item->total];
            });
        });

        // Generate labels untuk 12 bulan terakhir
        $labels = [];
        $setlabel = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthName = $bulan[$date->month - 1];
            $year = $date->year;
            $labels[] = $monthName . ' ' . $year;
            $setlabel[$date->month . '-' . $year] = $i;
        }

        // Inisialisasi data untuk setiap kategori
        $data12 = []; // Data untuk dikerjakan_oleh yang mengandung ',12'
        $data10 = []; // Data untuk dikerjakan_oleh yang mengandung ',10'

        // Proses setiap project
        foreach ($projects as $project) {
            foreach ($project as $oleh => $totale) {
                if ($oleh == 12) {
                    $data12[] = $totale;
                }
                if ($oleh == 10) {
                    $data10[] = $totale;
                }
            }
        }

        // Format data untuk chart
        $chartData = [
            'raw' => $projects,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Custom',
                    'data' => $data12,
                    'backgroundColor' => 'rgba(0, 139, 139, 0.6)',
                    'borderColor' => 'rgba(0, 139, 139, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Biasa',
                    'data' => $data10,
                    'backgroundColor' => 'rgba(255, 144, 33, 0.6)',
                    'borderColor' => 'rgba(255, 144, 33, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 5,
                ]
            ]
        ];

        return response()->json($chartData);
    }

    public function chart_project_saat_ini_webdeveloper()
    {
        $chartData = [];

        $chartData = [
            'labels' => ['Biasa belum dikerjakan', 'Biasa dalam pengerjaan', 'Custom belum dikerjakan', 'Custom dalam pengerjaan'],
            'datas' => [25, 1, 15, 5]
        ];

        return response()->json($chartData);
    }
}
